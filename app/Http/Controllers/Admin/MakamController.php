<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationTimelineLog;
use App\Services\ApplicationService;
use App\Services\EImzaService;
use App\Services\ProcessEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Makam Masası (Başkan / Karar Yeri)
 * ------------------------------------
 * Sistemdeki her modülde Başkan ve Yöneticiler en yetkilidir. Bu ekran,
 * "ÖNÜMDEKİ BEKLEYEN İMZALAR" listesini gösterir; tek tıkla
 * "ONAYLIYORUM E-İMZAYLA & GÖNDER" ile başvuru onaylanır, son adımda
 * Ön Kazı İzni verilir ve e-imza süreci başlatılır. PDF indirme blokları
 * hiyerarşiden (belediye rolü) geçerek açık kalır.
 */
class MakamController extends Controller
{
    public function __construct(protected ProcessEngine $engine)
    {
        $this->middleware(function (Request $request, $next) {
            $user = $request->user();
            if (! $user || (! $user->isMakam() && ! $this->engine->hasAnyStepRole($user))) {
                abort(403, 'Makam Masası yalnızca makam/yetkili rollerine açıktır.');
            }

            return $next($request);
        });
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $pending = $this->engine->pendingForUser($user);

        $recentApprovals = ApplicationTimelineLog::query()
            ->with(['application:id,application_no,status'])
            ->where('user_id', $user->id)
            ->whereIn('action', ['approval.step', 'pre_excavation.approved'])
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.makam.index', [
            'pending' => $pending,
            'recentApprovals' => $recentApprovals,
            'engine' => $this->engine,
        ]);
    }

    /**
     * Başkan Detayı — başvurunun SADECE karar için gerekli evrakını gösterir:
     * Üst Yazı, Ön Kazı İzni ve Ruhsat. Modüller/evrak arşivi yok.
     */
    public function show(Request $request, Application $application): View
    {
        $user = $request->user();

        $application->load([
            'institution',
            'creator',
            'surfaceLines.surfaceType',
            'preExcavationApprover',
            'staffApprover',
            'directorApprover',
            'viceMayorApprover',
            'timelineLogs.user',
            'history.user',
        ]);

        $currentStep = $this->engine->currentStep($application);

        // 16.08 (2. tur) FIX: Makam Masası artık adımın action_type'ına göre
        // GERÇEK e-imza/paraf akışını mı yoksa düz onayı mı göstereceğine karar
        // verir. Eskiden "ONAYLIYORUM E-İMZAYLA & GÖNDER" butonu adım e-imza
        // gerektirse bile hep düz onay yapıyordu — e-imza masaüstü uygulaması
        // hiçbir zaman açılmıyor, başvuru sessizce direkt onaylanıyordu.
        $actionType = $currentStep ? $this->engine->getStepActionType($currentStep) : 'onay';

        return view('admin.makam.show', [
            'application' => $application,
            'engine' => $this->engine,
            'processCurrentStep' => $currentStep,
            'processCurrentStepIsFinal' => $currentStep ? $this->engine->isLastStep($application) : false,
            'approvalLog' => $application->approval_log ?? [],
            'canApprove' => $this->engine->userCanApprove($application, $user),
            'currentStepLabel' => $this->engine->stageLabel($application->approval_stage),
            'actionType' => $actionType,
            'stepPdfType' => $currentStep ? $this->engine->getSignaturePdfType($currentStep) : null,
            'canSignStep' => $currentStep && $this->engine->canSignStep($currentStep, $user),
            'canParafStep' => $currentStep && $this->engine->canParafStep($currentStep, $user),
        ]);
    }

    /**
     * Tek tık: ONAYLIYORUM E-İMZAYLA & GÖNDER
     */
    public function onayla(Request $request, Application $application, ApplicationService $service, EImzaService $eimza): RedirectResponse
    {
        $user = $request->user();

        if (! $this->engine->userCanApprove($application, $user)) {
            abort(403, 'Bu başvuru şu an onayınıza açık değil.');
        }

        // 16.08 (2. tur) FIX: bu adım GERÇEK e-imza veya paraf gerektiriyorsa bu
        // route (düz onay) kullanılamaz — arayüz artık bu durumlarda GERÇEK
        // butonu (e-imza-btn / paraf formu) gösteriyor, ama bu uç nokta yine de
        // doğrudan çağrılabileceğinden (curl/URL) sunucu tarafında da kilitlenir.
        $currentStep = $this->engine->currentStep($application);
        if ($currentStep && $this->engine->stepRequiresSignature($currentStep)) {
            abort(422, 'Bu adım e-imza gerektiriyor. Lütfen "E-İmza At" butonunu kullanın.');
        }
        if ($currentStep && $this->engine->stepRequiresParaf($currentStep)) {
            abort(422, 'Bu adım paraf gerektiriyor. Lütfen "Paraf At" butonunu kullanın.');
        }

        $service->advanceApproval($user, $application, $user->name);

        $fresh = $application->fresh();
        $isFinal = $fresh->approval_stage === 'approved';

        $eimzaNote = '';
        if ($isFinal) {
            try {
                $txn = $eimza->baslat($fresh, 'pre_permit');
                $eimzaNote = " E-İmza işlemi başlatıldı (fiş: {$txn->transaction_id}).";
            } catch (\Throwable $e) {
                Log::warning('Makam e-imza başlatılamadı: '.$e->getMessage());
            }
        }

        $message = $isFinal
            ? "{$fresh->application_no} onaylandı — Ön Kazı İzni verildi.{$eimzaNote}"
            : "{$fresh->application_no} onaylandı, sıradaki adıma gönderildi ({$this->engine->stageLabel($fresh->approval_stage)}).";

        return redirect()->route('admin.makam.index')->with('success', $message);
    }
}
