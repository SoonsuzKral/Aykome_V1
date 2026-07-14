<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Events\ApplicationSubmitted;
use App\Models\Application;
use App\Models\ApplicationTimelineLog;
use App\Models\PreExcavationPermitSetting;
use App\Models\Receipt;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
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
                        'description' => $data['description'] ?? null,
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'address_text' => $data['address_text'] ?? null,
                        'width_m' => $data['width_m'] ?? null,
                        'length_m' => $data['length_m'] ?? null,
                        'deposit_amount' => $data['deposit_amount'] ?? null,
                        'excavation_amount' => $data['excavation_amount'] ?? null,
                    ]);

                    $year = now()->year;
                    $allNums = Application::query()
                        ->where('application_no', 'like', $year . '-%')
                        ->whereNotNull('application_no')
                        ->pluck('application_no');
                    $maxNo = $allNums->map(fn($v) => (int) substr($v, strrpos($v, '-') + 1))->max();
                    $nextNo = $maxNo > 0 ? $maxNo + 1 : 1;
                    $application->update([
                        'application_no' => sprintf('%s-%03d', $year, $nextNo),
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

                    if (! empty($data['surface_type_id'])) {
                        $this->pricingService->upsertSurfaceLine($application, $data);
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

    public function approvePreExcavation(User $user, Application $application): Application
    {
        $application->load(['institution', 'excavationAreas', 'surfaceLines.surfaceType', 'creator']);

        $application->update([
            'status' => ApplicationStatus::PreExcavationApproved,
            'pre_excavation_approved_at' => now(),
            'pre_excavation_approved_by' => $user->id,
        ]);

        // Generate pre-excavation permit PDF
        try {
            $settings = PreExcavationPermitSetting::getSingleton();

            $pdf = Pdf::loadView('admin.pdf.pre_excavation_permit', compact('application', 'settings'))
                ->setPaper('a4', 'portrait');

            $safeNo = $application->application_no ?: (string) $application->id;
            $pdfPath = 'pre-excavation-permits/pre-excavation-' . $safeNo . '-' . now()->format('YmdHis') . '.pdf';

            Storage::disk('public')->put($pdfPath, $pdf->output());

            $application->update([
                'pre_excavation_document_path' => $pdfPath,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[approvePreExcavation] PDF generation failed: ' . $e->getMessage());
        }

        $this->log($application, $user, 'pre_excavation.approved', [], 'Ön kazı izni onaylandı, belge üretildi');

        return $application->fresh();
    }

    public function submit(User $user, Application $application): Application
    {
        $application->update(['status' => ApplicationStatus::Submitted]);

        $this->pricingService->recalculateTotals($application);
        $this->log($application, $user, 'application.submitted', [], 'Başvuru belediyeye gönderildi');

        $fresh = $application->fresh(['institution', 'excavationAreas', 'surfaceLines.surfaceType', 'creator']);

        // Notify municipality users about the submitted application
        User::query()
            ->role(['super-admin', 'municipality-admin', 'municipality-staff'])
            ->where('id', '!=', $user->id)
            ->get()
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

            // Notify municipality admins to review receipt
            User::query()
                ->role(['super-admin', 'municipality-admin', 'municipality-staff'])
                ->get()
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
        DB::transaction(function () use ($user, $application, $licenseService) {
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

            $application->update([
                'status' => ApplicationStatus::Licensed,
                'receipt_approved_at' => now(),
                'receipt_approved_by' => $user->id,
                'payment_status' => 'paid',
                'approval_status' => 'licensed',
                'receipt_file_path' => $receiptMedia->getPathRelativeToRoot(),
            ]);

            $result = $licenseService->generateExcavationPermitPdf($application);
            $application->update(['license_document_path' => $result['path']]);

            $this->log($application, $user, 'receipt.approved', ['pdf' => $result['path'], 'receipt_id' => $receipt->id], 'Makbuz onaylandı, ruhsat PDF üretildi');
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
