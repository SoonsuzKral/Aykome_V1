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
            'pdf_type' => 'required|in:ruhsat,pre_permit,taahhutname,metraj,tahakkuk,cover_letter',
        ]);

        $application = Application::findOrFail($request->application_id);

        $transaction = $this->eImzaService->baslat($application, $request->pdf_type);

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
        ]);

        try {
            $transaction = EImzaTransaction::where('transaction_id', $request->transaction_id)->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'İşlem bulunamadı.'], 404);
        }

        if ($transaction->status !== 'pending') {
            return response()->json(['message' => 'İşlem zaten tamamlanmış veya süresi dolmuş.'], 400);
        }

        $this->eImzaService->tamamla(
            $transaction,
            file_get_contents($request->file('file')->getRealPath()),
            $request->imzalayan
        );

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
        ]);
    }
}
