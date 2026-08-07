<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationSubmitted;
use App\Models\Application;
use App\Models\ApplicationAudit;
use App\Models\ApplicationTimelineLog;
use App\Models\Receipt;
use App\Models\User;
use App\Notifications\NewApplicationCreatedNotification;
use App\Notifications\ReceiptUploadedNotification;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ApplicationService
{
    public function __construct(
        protected MapDrawingService $mapDrawingService,
        protected PricingService $pricingService,
    ) {}

    public function createDraft(User $user, array $data): Application
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($user, $data) {
                    $institutionId = $data['institution_id'] ?? $user->institution_id;
                    if ($institutionId === null) {
                        throw ValidationException::withMessages([
                            'institution_id' => 'Kurum seçimi zorunludur.',
                        ]);
                    }

                    $normalizedNationalId = preg_replace('/\D+/', '', (string) ($data['applicant_national_id'] ?? '')) ?: null;

                    $application = Application::query()->create([
                        'application_no' => null,
                        'institution_id' => $institutionId,
                        'created_by' => $user->id,
                        'status' => ApplicationStatus::Draft,
                        'applicant_first_name' => $data['applicant_first_name'],
                        'applicant_last_name' => $data['applicant_last_name'],
                        'applicant_national_id' => $normalizedNationalId,
                        'tc_no' => $data['tc_no'] ?? $normalizedNationalId,
                        'identity_no' => $data['identity_no'] ?? $normalizedNationalId,
                        'applicant_phone' => $data['applicant_phone'] ?? null,
                        'excavation_reason' => $data['excavation_reason'] ?? null,
                        'work_type' => $data['work_type'] ?? null,
                        'project_code' => $data['project_code'] ?? null,
                        'application_type' => $data['application_type'] ?? 'basvuru',
                        'description' => $data['description'] ?? null,
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'address_text' => $data['address_text'] ?? null,
                        'address_components' => $data['address_components'] ?? null,
                        'vice_mayor_name' => $data['vice_mayor_name'] ?? null,
                        'process_id' => $data['process_id'] ?? null,
                        'tesis_sorumlusu' => $data['tesis_sorumlusu'] ?? null,
                        'tesis_sorumlusu_adi' => $data['tesis_sorumlusu_adi'] ?? null,
                        'duzenleyen_kisi' => $data['duzenleyen_kisi'] ?? null,
                        'mudur_adi' => $data['mudur_adi'] ?? null,
                        'mudur_unvani' => $data['mudur_unvani'] ?? null,
                    ]);

                    $year = now()->year;
                    // Oracle unique constraint sorunu — id bazlı atama (en güvenilir yöntem)
                    $application->update([
                        'application_no' => sprintf('%s-%04d', $year, $application->id),
                    ]);

                    if (! empty($data['polygon_geojson']) || ! empty($data['total_area_m2'])) {
                        $this->mapDrawingService->syncPrimaryArea($application, [
                            'polygon_geojson' => $data['polygon_geojson'] ?? null,
                            'total_area_m2' => $data['total_area_m2'] ?? 0,
                            'center_lat' => $data['center_lat'] ?? null,
                            'center_lng' => $data['center_lng'] ?? null,
                            'address_text' => $data['address_text'] ?? null,
                        ]);
                        $application->update([
                            'total_area_m2' => $data['total_area_m2'] ?? $application->excavationAreas()->first()?->total_area_m2 ?? 0,
                        ]);
                    }

                    if (! empty($data['surface_lines']) && is_array($data['surface_lines'])) {
                        $this->pricingService->upsertSurfaceLines($application, $data['surface_lines']);
                        $this->pricingService->recalculateTotals($application);
                    }

                    $this->log($application, $user, 'application.created', [], 'Başvuru oluşturuldu');

                    $freshApp = $application->fresh(['institution', 'excavationAreas', 'surfaceLines.surfaceType', 'creator']);

                    return $freshApp;
                });
            } catch (QueryException $e) {
                if ($attempt === $maxAttempts || ! str_contains($e->getMessage(), 'APPLICATION_NO')) {
                    throw $e;
                }
                usleep(100_000);
            }
        }
    }

    /**
     * EK RUHSAT (Additional Permit) — asıl başvurudan KLON üretir.
     * Adres, kurum, başvuru sahibi ve iş bilgileri birebir kopyalanır;
     * yeni bir draft başvuru oluşur ve parent_id ile asıl başvuruya bağlanır.
     * Teminat kuralı: Ek Ruhsatta teminat kesilmez (PricingService'te is_additional_permit ile sıfırlanır).
     */
    public function createAdditionalPermit(User $user, Application $parent): Application
    {
        $parent->loadMissing(['institution', 'excavationAreas', 'surfaceLines.surfaceType']);

        return DB::transaction(function () use ($user, $parent) {
            $application = Application::query()->create([
                'application_no' => null,
                'parent_id' => $parent->id,
                'is_additional_permit' => true,
                'institution_id' => $parent->institution_id,
                'created_by' => $user->id,
                'status' => ApplicationStatus::Draft,
                'applicant_first_name' => $parent->applicant_first_name,
                'applicant_last_name' => $parent->applicant_last_name,
                'applicant_national_id' => $parent->applicant_national_id,
                'tc_no' => $parent->tc_no,
                'identity_no' => $parent->identity_no,
                'applicant_phone' => $parent->applicant_phone,
                'excavation_reason' => $parent->excavation_reason,
                'work_type' => $parent->work_type,
                'project_code' => $parent->project_code,
                'application_type' => 'ek_ruhsat',
                'description' => 'EK RUHSAT — ' . ($parent->description ?? 'Altyapı kazısı'),
                'start_date' => $parent->start_date,
                'end_date' => $parent->end_date,
                'address_text' => $parent->address_text,
                'address_components' => $parent->address_components,
                'vice_mayor_name' => $parent->vice_mayor_name,
                'process_id' => $parent->process_id,
                'tesis_sorumlusu' => $parent->tesis_sorumlusu,
                'tesis_sorumlusu_adi' => $parent->tesis_sorumlusu_adi,
                'duzenleyen_kisi' => $parent->duzenleyen_kisi,
                'mudur_adi' => $parent->mudur_adi,
                'mudur_unvani' => $parent->mudur_unvani,
            ]);

            $year = now()->year;
            $application->update([
                'application_no' => sprintf('%s-%04d', $year, $application->id),
            ]);

            // Kazı alanını kopyala
            $primaryArea = $parent->excavationAreas->first();
            if ($primaryArea) {
                $this->mapDrawingService->syncPrimaryArea($application, [
                    'polygon_geojson' => $primaryArea->polygon_geojson,
                    'total_area_m2' => $primaryArea->total_area_m2,
                    'center_lat' => $primaryArea->center_lat,
                    'center_lng' => $primaryArea->center_lng,
                    'address_text' => $primaryArea->address_text,
                ]);
            }

            $application->update([
                'total_area_m2' => $parent->total_area_m2 ?? $application->excavationAreas()->first()?->total_area_m2 ?? 0,
            ]);

            $this->log($application, $user, 'application.additional_permit_created', [], 'Asıl başvurudan Ek Ruhsat oluşturuldu');

            return $application->fresh(['institution', 'excavationAreas', 'surfaceLines.surfaceType', 'creator']);
        });
    }

    /**
     * Süreç & Onay Rotası motoru üzerinden onay silsilesini ilerletir.
     * Adım bilgisi process_steps tablosundan okunur; legacy kolonlar
     * (staff/director/vice_mayor) default süreç için eşzamanlı doldurulur.
     * Son adımda evrak onaylanır ve Ön Kazı İzni verilir.
     */
    public function advanceApproval(User $user, Application $application, ?string $viceMayorName = null): Application
    {
        $result = app(ProcessEngine::class)->approve($application, $user, $viceMayorName);

        if (! $result['approved']) {
            throw ValidationException::withMessages([
                'approval' => $result['reason'] === 'not_authorized'
                    ? 'Bu adımı onaylama yetkiniz bulunmuyor.'
                    : 'Onay rotası tanımlı değil veya adım bulunamadı.',
            ]);
        }

        $message = match ($result['stage']) {
            'staff' => 'Onay alındı. Başvuru Müdür onayına gönderildi.',
            'director' => 'Müdür onayı alındı. Başvuru Başkan Yardımcısı onayına gönderildi.',
            default => $result['finished']
                ? 'Ön kazı izni onaylandı.'
                : ($result['next']?->name ?? 'Sıradaki adım') . ' onayına gönderildi.',
        };

        $this->log(
            $application,
            $user,
            $result['finished'] ? 'pre_excavation.approved' : 'approval.step',
            [],
            $message
        );

        return $application->fresh(['institution', 'excavationAreas', 'surfaceLines.surfaceType', 'creator']);
    }

    private function getTargetedUsers(Application $application, ?int $excludeUserId = null): \Illuminate\Support\Collection
    {
        $query = User::query()
            ->where(function ($q) use ($application) {
                $q->role(['super-admin', 'municipality-admin', 'municipality-staff', 'municipality-buro', 'municipality-sef', 'municipality-mudur', 'municipality-makam']);
                if ($application->institution_id) {
                    $q->orWhere('institution_id', $application->institution_id);
                }
            });

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return $query->get();
    }

    public function submit(User $user, Application $application): Application
    {
        $engine = app(ProcessEngine::class);
        $firstStep = $engine->steps(null, $application)->first() ?? $engine->firstStep();

        $application->update([
            'status' => ApplicationStatus::Submitted,
            'approval_stage' => $firstStep?->role_key ?? 'staff',
        ]);

        $this->pricingService->recalculateTotals($application);
        $this->log($application, $user, 'application.submitted', [], 'Başvuru belediyeye gönderildi (Belediye personeli onayı bekleniyor)');

        $fresh = $application->fresh(['institution', 'excavationAreas', 'surfaceLines.surfaceType', 'creator']);

        // Targeted notification: admins see all, institution employees see only their own
        $this->getTargetedUsers($application, $user->id)
            ->each(fn (User $admin) => $admin->notify(new NewApplicationCreatedNotification($fresh)));

        // Real-time broadcast
        ApplicationSubmitted::dispatch($fresh);

        return $fresh;
    }

    public function approvePrice(User $user, Application $application): Application
    {
        $application->update([
            'status' => ApplicationStatus::AwaitingPayment,
            'price_approved_at' => now(),
            'price_approved_by' => $user->id,
            'approval_status' => 'price_approved',
        ]);

        $this->log($application, $user, 'price.approved', [], 'Keşif bedeli onaylandı');

        return $application->fresh();
    }

    public function addReceipt(
        Application $application,
        User $user,
        UploadedFile $file,
        ?string $notes = null,
    ): Receipt {
        return DB::transaction(function () use ($application, $user, $file, $notes) {
            $existingReceipt = $application->receipts()->latest('id')->first();

            if ($existingReceipt && in_array($existingReceipt->status, ['pending', 'rejected'], true)) {
                $receipt = $existingReceipt;
                $receipt->update([
                    'uploaded_by' => $user->id,
                    'status' => 'pending',
                    'notes' => $notes,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'review_notes' => null,
                ]);
            } else {
                $receipt = $application->receipts()->create([
                    'uploaded_by' => $user->id,
                    'status' => 'pending',
                    'notes' => $notes,
                ]);
            }

            $safeApplicationNo = $application->application_no ?: (string) $application->id;

            $storedPath = Storage::disk('public')->putFileAs(
                'receipts',
                $file,
                sprintf('receipt-%s-%s.%s', $safeApplicationNo, now()->format('YmdHis'), $file->getClientOriginalExtension())
            );

            if (! is_string($storedPath) || $storedPath === '') {
                throw ValidationException::withMessages([
                    'receipt_file' => 'Makbuz dosyası kaydedilemedi. Lütfen tekrar deneyin.',
                ]);
            }

            $receipt
                ->addMediaFromDisk($storedPath, 'public')
                ->usingName('receipt-'.$safeApplicationNo)
                ->usingFileName(basename($storedPath))
                ->toMediaCollection('scan', 'public');

            $updatePayload = [
                'payment_status' => 'receipt_uploaded',
                'receipt_file_path' => $storedPath,
                'approval_status' => 'pending',
            ];

            if ($application->status !== ApplicationStatus::ReceiptPending) {
                $updatePayload['status'] = ApplicationStatus::ReceiptPending;
            }

            $application->update($updatePayload);

            $this->log($application, $user, 'receipt.uploaded', ['receipt_id' => $receipt->id], 'Makbuz yüklendi ve onay sürecine alındı');

            // Notify admins + institution employees about receipt
            $this->getTargetedUsers($application, $user->id)
                ->each(fn (User $admin) => $admin->notify(new ReceiptUploadedNotification($application, $receipt)));

            return $receipt;
        });
    }

    public function rejectReceipt(User $user, Application $application, string $reviewNotes): Application
    {
        DB::transaction(function () use ($user, $application, $reviewNotes) {
            $receipt = $application->receipts()->latest('id')->first();

            if (! $receipt) {
                throw ValidationException::withMessages([
                    'receipt' => 'Reddedilecek makbuz bulunamadı.',
                ]);
            }

            $receipt->update([
                'status' => 'rejected',
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'review_notes' => $reviewNotes,
            ]);

            $application->update([
                'status' => ApplicationStatus::AwaitingPayment,
                'payment_status' => 'receipt_rejected',
                'approval_status' => 'pending',
            ]);

            $this->log($application, $user, 'receipt.rejected', ['receipt_id' => $receipt->id], 'Makbuz reddedildi: '.$reviewNotes);
        });

        return $application->fresh();
    }

    public function approveReceipt(User $user, Application $application, LicenseService $licenseService): Application
    {
        $isMunicipality = $application->institution?->is_municipality ?? false;

        DB::transaction(function () use ($user, $application, $licenseService, $isMunicipality) {
            $receipt = $application->receipts()->latest('id')->first();

            if (! $receipt) {
                throw ValidationException::withMessages([
                    'receipt' => 'Makbuz yüklenmeden onay verilemez.',
                ]);
            }

            $receiptMedia = $receipt->getFirstMedia('scan');

            if (! $receiptMedia) {
                throw ValidationException::withMessages([
                    'receipt' => 'Makbuz dosyası eksik. Lütfen makbuz görselini yükleyin.',
                ]);
            }

            if ($receipt->status !== 'approved') {
                $receipt->update([
                    'status' => 'approved',
                    'reviewed_by' => $user->id,
                    'reviewed_at' => now(),
                ]);
            }

            if ($isMunicipality) {
                $application->update([
                    'status' => ApplicationStatus::PreApproved,
                    'receipt_approved_at' => now(),
                    'receipt_approved_by' => $user->id,
                    'payment_status' => 'paid',
                    'approval_status' => 'payment_approved',
                ]);
                $this->log($application, $user, 'receipt.approved', ['receipt_id' => $receipt->id], 'Makbuz onaylandı, metraj aşamasına geçildi');
            } else {
                // KATI ADIM KAPISI: Alt kurum başvurusunda makbuz onayı RUHSATI otomatik üretmez.
                // Modül, belediyenin "RUHSAT MODÜLÜNÜ AÇ" (openRuhsat) tıklamasıyla açılır ve
                // ruhsat PDF'i ancak o aşamada üretilir (payment_completed → licensed).
                $application->update([
                    'status' => ApplicationStatus::PaymentCompleted,
                    'licensed_at' => null,
                    'receipt_approved_at' => now(),
                    'receipt_approved_by' => $user->id,
                    'payment_status' => 'paid',
                    'approval_status' => 'payment_completed',
                    'receipt_file_path' => $receiptMedia->getPathRelativeToRoot(),
                ]);

                $this->log(
                    $application,
                    $user,
                    'receipt.approved',
                    ['receipt_id' => $receipt->id],
                    'Makbuz onaylandı, ödeme tamamlandı. Ruhsat modülü belediye yetkisiyle açılacak'
                );
            }
        });

        return $application->fresh();
    }

    public function log(Application $application, ?User $user, string $action, array $meta = [], ?string $message = null): ApplicationTimelineLog
    {
        return $application->timelineLogs()->create([
            'user_id' => $user?->id,
            'action' => $action,
            'meta' => $meta,
            'message' => $message,
        ]);
    }
}
