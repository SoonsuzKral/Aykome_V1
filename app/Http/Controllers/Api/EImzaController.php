<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\EImzaTransaction;
use App\Services\EImzaService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EImzaController extends Controller
{
    public function __construct(
        protected EImzaService $eImzaService
    ) {}

    public function baslat(Request $request)
    {
        $request->validate([
            'application_id' => 'required|exists:applications,id',
            'pdf_type' => 'required|string',
        ]);

        // UI'dan gelen modül anahtarlarını (örn. cover_letter_signed, on_kazi_signed,
        // metraj_signed, taahhutname_imzali, ruhsat_teslim) gerçek PDF tipine normalize et.
        $pdfTypeMap = [
            'cover_letter_signed' => 'cover_letter',
            'on_kazi_signed'      => 'pre_permit',
            'metraj_signed'       => 'metraj',
            'taahhutname_imzali'  => 'taahhutname',
            'ruhsat_teslim'       => 'ruhsat',
        ];
        $pdfType = $pdfTypeMap[$request->pdf_type] ?? $request->pdf_type;

        $allowed = ['ruhsat', 'pre_permit', 'taahhutname', 'metraj', 'tahakkuk', 'makbuz', 'cover_letter'];
        if (! in_array($pdfType, $allowed, true)) {
            return response()->json(['message' => "Geçersiz PDF türü: {$request->pdf_type}"], 422);
        }

        $application = Application::findOrFail($request->application_id);

        // GÖREV 3 — Sunucu tarafı yetki zorunluluğu: butonu gizlemek tek başına
        // güvenlik sağlamaz, bu endpoint doğrudan da çağrılabilir. Süreç adımında
        // rolü olan belediye personeli veya kendi başvurusunda update yetkisi olan
        // alt kurum kullanıcısı e-imza başlatabilir.
        $user = auth()->user();
        $engine = app(\App\Services\ProcessEngine::class);
        $step = $engine->currentStep($application);
        $yetkiliMi = $user->isMunicipalityPersonel()
            ? ($step !== null && $engine->roleCanApproveStep($step, $user))
            : $user->can('update', $application);
        if (! $yetkiliMi) {
            return response()->json(['message' => 'Bu adım için e-imza yetkiniz yok.'], 403);
        }

        // GÖREV 6: İmzalayan bilgisi giriş yapmış kullanıcıdan otomatik alınır
        // (ad/soyad + rol → Türkçe unvan); UI'dan hiçbir form sorulmaz.
        $transaction = $this->eImzaService->baslat(
            $application,
            $pdfType,
            EImzaService::kullanicidanImzalayan($user)
        );

        return response()->json([
            'transaction_id' => $transaction->transaction_id,
            'token' => $transaction->token,
            'expires_at' => $transaction->expires_at->toIso8601String(),
        ]);
    }

    public function pdf(Request $request, string $transactionId)
    {
        try {
            $transaction = EImzaTransaction::where('transaction_id', $transactionId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'İşlem bulunamadı.'], 404);
        }

        if (!$this->eImzaService->tokenDogrula($transaction, $request->token)) {
            return response()->json(['message' => 'Geçersiz veya süresi dolmuş işlem.'], 403);
        }

        $path = $this->eImzaService->pdfIndir($transaction);
        if (!$path) {
            return response()->json(['message' => 'PDF bulunamadı.'], 404);
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="belge.pdf"',
        ]);
    }

    public function tamamla(Request $request)
    {
        $request->validate([
            'transaction_id' => 'required|exists:e_imza_transactions,transaction_id',
            'file' => 'required|file|mimes:pdf|max:20480',
            'imzalayan' => 'required|array',
            'imzalayan.ad' => 'required|string',
            'imzalayan.soyad' => 'required|string',
            'imzalayan.tckn' => 'required|string',
            'imzalayan.sertifika_turu' => 'required|string',
            // GÖREV 2 — Akıllı kart sertifika kimlik adı (Subject CN). Electron
            // uploader.js'ten sertifikanın kendisinden gönderilir.
            'certificate_cn' => 'nullable|string',
        ]);

        try {
            $transaction = EImzaTransaction::where('transaction_id', $request->transaction_id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'İşlem bulunamadı.'], 404);
        }

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'İşlem zaten tamamlanmış veya süresi dolmuş.'], 400);
        }

        try {
            $this->eImzaService->tamamla(
                $transaction,
                file_get_contents($request->file('file')->getRealPath()),
                $request->imzalayan,
                $request->input('certificate_cn')
            );
        } catch (\App\Exceptions\EImzaSahibiUyusmazlikException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 403);
        }

        return response()->json(['status' => 'completed']);
    }

    public function indir(Request $request, string $transactionId)
    {
        try {
            $transaction = EImzaTransaction::where('transaction_id', $transactionId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            abort(404, 'İşlem bulunamadı.');
        }

        $signedPath = $transaction->imzali_pdf;
        if (!$signedPath || !Storage::disk('public')->exists($signedPath)) {
            abort(404, 'İmzalı PDF bulunamadı.');
        }

        return response()->file(Storage::disk('public')->path($signedPath), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="imzali-belge.pdf"',
        ]);
    }

    public function durum(Request $request, string $transactionId)
    {
        try {
            $transaction = EImzaTransaction::where('transaction_id', $transactionId)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'İşlem bulunamadı.'], 404);
        }

        return response()->json([
            'status' => $transaction->status,
            'completed_at' => $transaction->completed_at?->toIso8601String(),
            'imzalayan_info' => $transaction->imzalayan_info,
            'imzali_url' => $transaction->imzali_pdf && Storage::disk('public')->exists($transaction->imzali_pdf)
                ? route('e-imza.indir', ['transactionId' => $transaction->transaction_id], false)
                : null,
        ]);
    }
}
