<?php

namespace App\Http\Controllers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EBYS Elektronik Belge Doğrulama Portalı (PUBLIC)
 * -------------------------------------------------
 * Giriş yapmayan herkesin erişebildiği korumasız doğrulama sayfası.
 * KVKK GİZLİLİK: Sonuç ekranında TC/telefon maskelenir, şahıs adı yalnızca
 * baş harflerle (örn. "M K") temsil edilir; kurumsal ad açık gösterilir.
 * Hiçbir şekilde dosya indirme / belge içeriği gösterilmez — yalnızca doğrulama.
 */
class DocumentVerificationController extends Controller
{
    public function index(): View
    {
        return view('verification.index');
    }

    public function verifyDocument(Request $request): View
    {
        $code = mb_strtoupper(trim((string) $request->input('verification_code')), 'UTF-8');

        $request->validate([
            'verification_code' => ['required', 'string', 'max:20'],
        ]);

        if ($code === '') {
            return view('verification.result', [
                'success' => false,
                'message' => 'Doğrulama kodu boş olamaz. Lütfen evrak üzerindeki BELGE DOĞRULAMA KODU değerini giriniz.',
            ]);
        }

        $application = Application::query()
            ->with('institution:id,name')
            ->where('verification_code', $code)
            ->first();

        if (! $application) {
            return view('verification.result', [
                'success' => false,
                'message' => 'Bu doğrulama kodu ile eşleşen bir belge bulunamadı. Kodu kontrol edip tekrar deneyiniz.',
            ]);
        }

        return view('verification.result', [
            'success' => true,
            'message' => 'Belge Sistemimizde Orijinaldir.',
            'application' => $this->maskedPayload($application),
        ]);
    }

    /** KVKK uyumlu güvenli görünüm: hassas veriler maskelenir, kurumsal ad açık kalır. */
    protected function maskedPayload(Application $application): array
    {
        $status = $application->status instanceof ApplicationStatus
            ? $application->status->label()
            : (string) $application->status;

        return [
            'application_no' => $application->application_no,
            'verification_code' => $application->verification_code,
            'status' => $status,
            'is_cancelled' => $application->status instanceof ApplicationStatus && $application->status === ApplicationStatus::Cancelled,
            'start_date' => optional($application->start_date)->format('d.m.Y') ?? '—',
            'end_date' => optional($application->end_date)->format('d.m.Y') ?? '—',
            'excavation_reason' => $application->excavation_reason,
            'institution' => trim((string) ($application->institution?->name ?? '')),
            'applicant' => $this->maskName($application->applicant_first_name . ' ' . $application->applicant_last_name),
            'national_id' => $this->maskSensitive($application->tc_no ?? $application->applicant_national_id),
            'phone' => $this->maskSensitive($application->applicant_phone),
            'is_institution_application' => $application->isInstitutionApplication(),
        ];
    }

    /** "Mehmet Kaya" → "M K" (baş harfler) */
    protected function maskName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '—';
        }

        $parts = preg_split('/\s+/', $name) ?: [];

        return collect($parts)
            ->filter()
            ->map(fn (string $p) => mb_strtoupper(mb_substr($p, 0, 1, 'UTF-8'), 'UTF-8'))
            ->implode(' ');
    }

    /** TC/telefon → son 4 hane göster, geri kalanı yıldızla: "****1212" */
    protected function maskSensitive(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '—';
        }

        return '****' . mb_substr(preg_replace('/\D+/', '', $value), -4);
    }
}
