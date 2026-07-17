<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Events\ReceiptUploaded;
use App\Http\Controllers\Controller;
use App\Http\Requests\RejectReceiptRequest;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Requests\StoreReceiptRequest;
use App\Http\Requests\TransferTaskRequest;
use App\Models\Application;
use App\Models\Institution;
use App\Models\PreExcavationPermitSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PermitSetting;
use App\Services\ApplicationService;
use App\Services\AuditLogger;
use App\Services\LicenseService;
use App\Services\MapDrawingService;
use App\Services\PricingService;
use App\Services\TaskTransferService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ApplicationsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Application::class);

        $user = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'institution_id' => trim((string) $request->query('institution_id', '')),
        ];

        $query = Application::query()->with(['institution', 'creator'])->latest();

        // ── Data isolation ────────────────────────────────────────────────
        if ($user->hasRole('field-team')) {
            // Saha personeli: sadece kendisine atanmış görevlerdeki başvurular
            $query->whereHas('fieldTasks', fn ($q) => $q->where('assigned_to', $user->id));
        } elseif (! $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])) {
            // Kurum çalışanı: sadece kendi kurumunun başvuruları
            $query->where('institution_id', $user->institution_id);
            $filters['institution_id'] = (string) $user->institution_id;
        }

        if ($filters['q'] !== '') {
            $needle = $filters['q'];
            $query->where(function ($q) use ($needle): void {
                $q->where('application_no', 'like', "%{$needle}%")
                    ->orWhere('applicant_first_name', 'like', "%{$needle}%")
                    ->orWhere('applicant_last_name', 'like', "%{$needle}%")
                    ->orWhere('applicant_national_id', 'like', "%{$needle}%")
                    ->orWhere('address_text', 'like', "%{$needle}%");
            });
        }

        $statusValues = collect(ApplicationStatus::cases())->map(fn (ApplicationStatus $status) => $status->value);

        if ($filters['status'] !== '' && $statusValues->contains($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $filters['status'] = '';
        }

        if ($filters['institution_id'] !== '' && $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])) {
            $institutionId = (int) $filters['institution_id'];
            if ($institutionId > 0) {
                $query->where('institution_id', $institutionId);
            }
        }

        return view('admin.applications.index', [
            'applications' => $query->paginate(15)->withQueryString(),
            'filters' => $filters,
            'statuses' => ApplicationStatus::cases(),
            'institutions' => $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])
                ? Institution::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Application::class);

        $user = $request->user();
        $isInstitutionUser = ! $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff']);

        $institutions = $isInstitutionUser
            ? Institution::query()->where('id', $user->institution_id)->get(['id', 'name', 'slug', 'color_code', 'is_municipality'])
            : Institution::query()->orderBy('name')->get(['id', 'name', 'slug', 'color_code', 'is_municipality']);

        $applicantPrefill = null;
        if ($isInstitutionUser) {
            $nationalId = preg_replace('/\D+/', '', (string) ($user->national_id ?? ''));
            $nameParts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = mb_convert_case((string) (array_shift($nameParts) ?: ''), MB_CASE_TITLE, 'UTF-8');
            $lastName = mb_convert_case(trim(implode(' ', $nameParts)), MB_CASE_TITLE, 'UTF-8');

            $applicantPrefill = [
                'first_name' => $firstName ?: $user->name,
                'last_name' => $lastName,
                'national_id' => $nationalId,
                'national_id_masked' => $this->maskNationalId($nationalId),
                'phone' => $user->phone ?? '',
            ];
        }

        return view('admin.applications.create', [
            'institutions' => $institutions,
            'surfaceTypes' => \App\Models\SurfaceType::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'price_per_m2']),
            'googleMapsApiKey' => config('services.google_maps.api_key') ?: config('aykome.google_maps_api_key'),
            'isInstitutionUser' => $isInstitutionUser,
            'applicantPrefill' => $applicantPrefill,
        ]);
    }

    public function store(StoreApplicationRequest $request, ApplicationService $service): RedirectResponse
    {
        $validated = $request->validated();

        // ── Mevzuat güvenlik duvarı: kurum personeli vatandaş adına başvuru AÇAMAZ ──
        // Request'ten gelen applicant alanları tamamen görmezden gelinir;
        // zorunlu olarak oturum açan kullanıcının kendi bilgileri yazılır.
        $user = $request->user();
        if (! $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])) {
            $nationalId = preg_replace('/\D+/', '', (string) ($user->national_id ?? '')) ?: null;
            $nameParts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $validated['applicant_first_name'] = mb_convert_case((string) (array_shift($nameParts) ?: ''), MB_CASE_TITLE, 'UTF-8');
            $validated['applicant_last_name'] = mb_convert_case(trim(implode(' ', $nameParts)), MB_CASE_TITLE, 'UTF-8');
            $validated['applicant_national_id'] = $nationalId;
            $validated['tc_no'] = $nationalId;
            $validated['identity_no'] = $nationalId;
            $validated['applicant_phone'] = $user->phone ?? null;
        }

        $validated['applicant_national_id'] = preg_replace('/\D+/', '', (string) ($validated['applicant_national_id'] ?? '')) ?: null;
        $validated['tc_no'] = preg_replace('/\D+/', '', (string) ($validated['tc_no'] ?? $validated['applicant_national_id'] ?? '')) ?: $validated['applicant_national_id'];
        $validated['identity_no'] = preg_replace('/\D+/', '', (string) ($validated['identity_no'] ?? $validated['applicant_national_id'] ?? '')) ?: $validated['applicant_national_id'];

        $application = $service->createDraft($request->user(), $validated);

        $this->handleDocumentUploads($request, $application);

        AuditLogger::log('application.create', "Yeni başvuru oluşturuldu: {$application->application_no}", 'Application', $application->id);
        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Başvuru taslak olarak kaydedildi.');
    }

    public function checkApplicant(Request $request)
    {
        $request->validate([
            'applicant_national_id' => ['nullable', 'string'],
            'identity_no' => ['nullable', 'string'],
        ]);

        $identityNo = preg_replace('/\D+/', '', (string) ($request->input('applicant_national_id') ?: $request->input('identity_no')));

        if (! is_string($identityNo) || $identityNo === '') {
            return response()->json([
                'found' => false,
                'message' => 'Geçerli bir TCKN girin.',
            ], 422);
        }

        if (strlen($identityNo) !== 11) {
            return response()->json([
                'found' => false,
                'message' => 'TCKN 11 haneli olmalıdır.',
            ], 422);
        }

        $applicationQuery = Application::query()
            ->select(['applicant_first_name', 'applicant_last_name', 'applicant_national_id', 'applicant_phone', 'address_text'])
            ->where('applicant_national_id', $identityNo);

        if (! $request->user()->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])) {
            $applicationQuery->where('institution_id', $request->user()->institution_id);
        }

        $application = $applicationQuery->latest('id')->first();

        if ($application) {
            AuditLogger::log('tckn.query', "TCKN sorgulandı: {$identityNo} — başvuru kaydı bulundu.", 'Application', null, ['tckn' => $identityNo, 'source' => 'application']);
            return response()->json([
                'found' => true,
                'source' => 'application',
                'data' => [
                    'applicant_first_name' => $application->applicant_first_name,
                    'applicant_last_name' => $application->applicant_last_name,
                    'applicant_national_id' => $application->applicant_national_id,
                    'applicant_phone' => $application->applicant_phone,
                    'address_text' => $application->address_text,
                ],
            ]);
        }

        $userQuery = User::query()
            ->select(['name', 'phone', 'national_id'])
            ->where('national_id', $identityNo);

        if (! $request->user()->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])) {
            $userQuery->where(function (Builder $query) use ($request): void {
                $query
                    ->whereNull('institution_id')
                    ->orWhere('institution_id', $request->user()->institution_id);
            });
        }

        $user = $userQuery->latest('id')->first();

        if ($user) {
            AuditLogger::log('tckn.query', "TCKN sorgulandı: {$identityNo} — kullanıcı kaydı bulundu.", 'User', $user->id, ['tckn' => $identityNo, 'source' => 'user']);
            $nameParts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = mb_convert_case((string) (array_shift($nameParts) ?: ''), MB_CASE_TITLE, 'UTF-8');
            $lastName = mb_convert_case(trim(implode(' ', $nameParts)), MB_CASE_TITLE, 'UTF-8');

            return response()->json([
                'found' => true,
                'source' => 'user',
                'data' => [
                    'applicant_first_name' => $firstName,
                    'applicant_last_name' => $lastName,
                    'applicant_national_id' => $user->national_id,
                    'applicant_phone' => $user->phone,
                ],
            ]);
        }

        AuditLogger::log('tckn.query', "TCKN sorgulandı: {$identityNo} — kayıt bulunamadı.", null, null, ['tckn' => $identityNo]);
        return response()->json([
            'found' => false,
            'message' => 'Bu TCKN için kayıt bulunamadı.',
        ]);
    }

    public function show(Request $request, Application $application): View
    {
        $this->authorize('view', $application);

        $application->load([
            'institution',
            'creator',
            'excavationAreas',
            'surfaceLines.surfaceType',
            'preExcavationApprover',
            'timelineLogs.user',
            'fieldTasks.assignee',
            'receipts.uploader',
            'receipts.reviewer',
            'documents',
        ]);

        $fieldUsers = User::query()
            ->role('field-team')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.applications.show', [
            'application' => $application,
            'fieldUsers' => $fieldUsers,
            'googleMapsApiKey' => config('services.google_maps.api_key') ?: config('aykome.google_maps_api_key'),
            'can' => [
                'update' => $request->user()->can('update', $application),
                'approve_pre_excavation' => $request->user()->can('approvePreExcavation', $application),
                'approve_price' => $request->user()->can('approvePrice', $application),
                'approve_receipt' => $request->user()->can('approveReceipt', $application),
                'transfer' => $request->user()->can('transferTask', $application),
                'reject_receipt' => $request->user()->can('approveReceipt', $application),
            ],
        ]);
    }

    public function edit(Request $request, Application $application): View
    {
        $this->authorize('update', $application);

        $user = $request->user();
        $application->load(['institution:id,name,slug,color_code,is_municipality', 'excavationAreas', 'documents']);
        $area = $application->excavationAreas->sortByDesc('updated_at')->first();

        $institutions = $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])
            ? Institution::query()->orderBy('name')->get(['id', 'name', 'slug', 'color_code', 'is_municipality'])
            : Institution::query()->where('id', $user->institution_id)->get(['id', 'name', 'slug', 'color_code', 'is_municipality']);

        return view('admin.applications.edit', [
            'application' => $application,
            'institutions' => $institutions,
            'surfaceTypes' => \App\Models\SurfaceType::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'price_per_m2']),
            'currentSurfaceTypeId' => $application->surfaceLines()->first()?->surface_type_id,
            'googleMapsApiKey' => config('services.google_maps.api_key') ?: config('aykome.google_maps_api_key'),
            'drawing' => [
                'polygon_geojson' => $area?->polygon_geojson,
                'total_area_m2' => $area?->total_area_m2 ?? $application->total_area_m2,
                'center_lat' => $area?->center_lat,
                'center_lng' => $area?->center_lng,
            ],
        ]);
    }

    public function update(
        Request $request,
        Application $application,
        MapDrawingService $mapDrawingService,
        PricingService $pricingService,
    ): RedirectResponse {
        $this->authorize('update', $application);

        // Virgüllü ondalık ayracını noktaya çevir (Türkçe format desteği)
        foreach (['width_m', 'length_m', 'quantity', 'multiplier', 'total_area_m2', 'deposit_amount', 'excavation_amount'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $val = $request->input($field);
                $val = str_replace('.', '', $val);   // binlik ayracını kaldır
                $val = str_replace(',', '.', $val);  // virgülü noktaya çevir
                $request->merge([$field => $val !== '' ? $val : null]);
            }
        }

        $data = $request->validate([
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'applicant_phone' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string'],
            'address_text' => ['nullable', 'string', 'max:500'],
            'polygon_geojson' => ['nullable', 'string'],
            'total_area_m2' => ['nullable', 'numeric', 'min:0'],
            'center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'surface_type_id' => ['nullable', 'exists:surface_types,id'],
            'width_m' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'length_m' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'multiplier' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'excavation_amount' => ['nullable', 'numeric', 'min:0'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimetypes:application/pdf,image/jpeg,image/png,image/jpg,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/webp,image/gif,image/bmp,image/tiff', 'max:51200'],
        ]);

        $user = $request->user();
        if (! $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])) {
            unset($data['institution_id']);
        }

        $polygonGeoJson = $data['polygon_geojson'] ?? null;
        $totalAreaM2 = isset($data['total_area_m2']) ? (float) $data['total_area_m2'] : null;

        if (($totalAreaM2 === null || $totalAreaM2 <= 0) && is_string($polygonGeoJson) && $polygonGeoJson !== '') {
            $totalAreaM2 = $mapDrawingService->calculateAreaM2FromGeoJson($polygonGeoJson);
        }

        if (
            $polygonGeoJson !== null
            || array_key_exists('total_area_m2', $data)
            || array_key_exists('center_lat', $data)
            || array_key_exists('center_lng', $data)
            || array_key_exists('address_text', $data)
        ) {
            $mapDrawingService->syncPrimaryArea($application, [
                'polygon_geojson' => $polygonGeoJson,
                'total_area_m2' => $totalAreaM2 ?? 0,
                'center_lat' => $data['center_lat'] ?? null,
                'center_lng' => $data['center_lng'] ?? null,
                'address_text' => $data['address_text'] ?? $application->address_text,
            ]);
        }

        $application->update([
            'institution_id' => $data['institution_id'] ?? $application->institution_id,
            'applicant_phone' => $data['applicant_phone'] ?? null,
            'description' => $data['description'] ?? null,
            'address_text' => $data['address_text'] ?? $application->address_text,
            'total_area_m2' => $totalAreaM2 ?? ($data['total_area_m2'] ?? $application->total_area_m2),
            'width_m' => $data['width_m'] ?? $application->width_m,
            'length_m' => $data['length_m'] ?? $application->length_m,
            'deposit_amount' => $data['deposit_amount'] ?? $application->deposit_amount,
            'excavation_amount' => $data['excavation_amount'] ?? $application->excavation_amount,
        ]);

        $this->handleDocumentUploads($request, $application);

        if (! empty($data['surface_type_id'])) {
            $pricingService->upsertSurfaceLine($application, $data);
            $pricingService->recalculateTotals($application);
        }

        AuditLogger::log('application.update', "Başvuru güncellendi: {$application->application_no}", 'Application', $application->id);
        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Başvuru güncellendi.');
    }

    private function handleDocumentUploads(Request $request, Application $application): void
    {
        if (! $request->hasFile('documents')) {
            return;
        }

        foreach ($request->file('documents') as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $safeName = time() . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $path = $file->storeAs('application-documents/' . $application->id, $safeName, 'public');

            $application->documents()->create([
                'original_name' => $originalName,
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }
    }

    public function submit(Request $request, Application $application, ApplicationService $service): RedirectResponse
    {
        $this->authorize('update', $application);

        $service->submit($request->user(), $application);

        return back()->with('success', 'Başvuru belediyeye iletildi.');
    }

    public function approvePreExcavation(Request $request, Application $application, ApplicationService $service): RedirectResponse
    {
        $this->authorize('approvePreExcavation', $application);

        $service->approvePreExcavation($request->user(), $application);

        AuditLogger::log('pre_excavation.approve', "Ön kazı izni onaylandı: {$application->application_no}", 'Application', $application->id);
        return back()->with('success', 'Ön kazı izni onaylandı ve belge PDF üretildi.');
    }

    public function downloadPreExcavationPermit(Application $application)
    {
        $this->authorize('view', $application);

        if (! $application->pre_excavation_document_path || ! Storage::disk('public')->exists($application->pre_excavation_document_path)) {
            abort(404, 'Ön kazı izin belgesi bulunamadı.');
        }

        return Storage::disk('public')->download(
            $application->pre_excavation_document_path,
            'on-kazi-izni-' . $application->application_no . '.pdf'
        );
    }

    public function approvePrice(Request $request, Application $application, ApplicationService $service): RedirectResponse
    {
        $this->authorize('approvePrice', $application);

        $service->approvePrice($request->user(), $application);

        AuditLogger::log('price.approve', "Fiyat onaylandı: {$application->application_no}", 'Application', $application->id);
        return back()->with('success', 'Fiyat onayı verildi.');
    }

    public function approveReceipt(Request $request, Application $application, ApplicationService $service, LicenseService $licenseService): RedirectResponse
    {
        $this->authorize('approveReceipt', $application);

        // Onay formuyla birlikte yeni bir dosya gönderildiyse önce kaydet
        if ($request->hasFile('receipt_file')) {
            $request->validate([
                'receipt_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            ]);
            $service->addReceipt(
                $application,
                $request->user(),
                $request->file('receipt_file'),
                $request->input('notes'),
            );
            // Modeli tazele
            $application->refresh();
        }

        $service->approveReceipt($request->user(), $application, $licenseService);

        AuditLogger::log('receipt.approve', "Makbuz onaylandı, ruhsat üretildi: {$application->application_no}", 'Application', $application->id);
        return back()->with('success', 'Makbuz onaylandı ve ruhsat PDF oluşturuldu.');
    }

    public function rejectReceipt(RejectReceiptRequest $request, Application $application, ApplicationService $service): RedirectResponse
    {
        $service->rejectReceipt(
            $request->user(),
            $application,
            (string) $request->validated('review_notes')
        );

        AuditLogger::log('receipt.reject', "Makbuz reddedildi: {$application->application_no}", 'Application', $application->id);
        return back()->with('success', 'Makbuz reddedildi. Başvuru ödeme bekleyen duruma alındı.');
    }

    public function storeReceipt(StoreReceiptRequest $request, Application $application, ApplicationService $service): RedirectResponse
    {
        $uploadedFile = $request->file('receipt_file');
        $validated = $request->validated();

        $service->addReceipt(
            $application,
            $request->user(),
            $uploadedFile,
            $validated['notes'] ?? null,
        );

        // Real-time broadcast — adminlere makbuz yüklendi bildirimi
        try {
            ReceiptUploaded::dispatch($application->load('institution'));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('[storeReceipt] ReceiptUploaded broadcast hatası: ' . $e->getMessage());
        }

        AuditLogger::log('receipt.upload', "Makbuz yüklendi: {$application->application_no}", 'Application', $application->id);
        return back()->with('success', 'Makbuz yüklendi. Belediye onayı bekleniyor.');
    }

    public function transfer(TransferTaskRequest $request, Application $application, TaskTransferService $transferService): RedirectResponse
    {
        $assignee = User::query()->findOrFail($request->validated('assigned_to'));
        $transferService->assignFieldTask(
            $application,
            $assignee,
            $request->user(),
            $request->validated('notes'),
            $request->validated('due_date')
        );

        AuditLogger::log('task.transfer', "Saha görevi devredildi: {$application->application_no} → {$assignee->name}", 'Application', $application->id);
        return back()->with('success', 'Saha görevi devredildi.');
    }

    public function downloadLicense(Request $request, Application $application)
    {
        $this->authorize('view', $application);

        if (! $application->license_document_path || ! Storage::disk('local')->exists($application->license_document_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $application->license_document_path,
            'ruhsat-'.$application->application_no.'.pdf'
        );
    }

    /**
     * Generate the payment/cashier receipt PDF (Tahsilat Makbuzu).
     * Handed to the citizen to take to the cashier desk for payment.
     */
    public function generatePaymentReceipt(Application $application): Response
    {
        $this->authorize('view', $application);

        $application->load(['institution']);

        $pdf = Pdf::loadView('admin.pdf.tahsilat_makbuzu', compact('application'))
            ->setPaper('a4', 'portrait');

        AuditLogger::log(
            'payment_receipt.downloaded',
            "Tahsilat makbuzu indirildi: {$application->application_no}",
            'Application',
            $application->id,
        );

        return $pdf->download('tahsilat-makbuzu-' . $application->application_no . '.pdf');
    }

    /**
     * Dynamically generate permit PDF using current PermitSettings (logo, signature, stamp).
     * Called from the "Ruhsat Belgesi Al" button — always fresh, reflects latest admin settings.
     */
    public function downloadPermitLive(Application $application): Response
    {
        $this->authorize('view', $application);

        $application->load([
            'institution',
            'creator',
            'excavationAreas',
            'surfaceLines.surfaceType',
            'priceApprover',
            'receiptApprover',
        ]);

        $pdf = Pdf::loadView('admin.pdf.ruhsat', compact('application'))
            ->setPaper('a4', 'portrait');

        AuditLogger::log(
            'permit.downloaded',
            "Ruhsat belgesi indirildi: {$application->application_no}",
            'Application',
            $application->id,
        );

        return $pdf->download('ruhsat-' . $application->application_no . '.pdf');
    }

    public function statusJson(Request $request, Application $application): \Illuminate\Http\JsonResponse
    {
        $this->authorize('view', $application);

        $application->refresh();
        $status = $application->status instanceof ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        ['label' => $label, 'class' => $class] = $this->statusBadgeMeta($status);

        return response()->json([
            'status'      => $status,
            'label'       => $label,
            'badge_class' => $class,
            'updated_at'  => $application->updated_at?->toIso8601String(),
        ]);
    }

    public function data(Request $request): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Application::class);

        $user  = $request->user();
        $query = Application::query()
            ->with(['institution', 'creator'])
            ->select('applications.*');

        // ── Data isolation ────────────────────────────────────────────────
        if ($user->hasRole('field-team')) {
            $query->whereHas('fieldTasks', fn ($q) => $q->where('assigned_to', $user->id));
        } elseif (! $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])) {
            $query->where('institution_id', $user->institution_id);
        }

        // Status filter (custom param)
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Institution filter (super-admin / municipality only)
        if ($instId = $request->input('institution_id')) {
            if ($user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])) {
                $query->where('institution_id', (int) $instId);
            }
        }

        // DataTables global search (debounced on frontend)
        if ($search = $request->input('search.value')) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('application_no',       'like', "%{$search}%")
                  ->orWhere('applicant_first_name','like', "%{$search}%")
                  ->orWhere('applicant_last_name', 'like', "%{$search}%")
                  ->orWhere('applicant_national_id','like', "%{$search}%")
                  ->orWhere('address_text',         'like', "%{$search}%")
                  ->orWhereHas('institution', fn ($r) => $r->where('name', 'like', "%{$search}%"));
            });
        }

        $totalFiltered = (clone $query)->count();

        // Ordering
        $colMap     = ['id', 'application_no', 'applicant_last_name', 'institution_id', 'status', 'address_text', 'created_at'];
        $orderCol   = (int) $request->input('order.0.column', 6);
        $orderDir   = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $orderField = $colMap[$orderCol] ?? 'created_at';

        $query->orderBy($orderField, $orderDir);

        $rows = $query
            ->offset((int) $request->input('start', 0))
            ->limit((int) $request->input('length', 25))
            ->get();

        $statusLabels = $this->statusLabels();

        $data = $rows->map(function (Application $app) use ($statusLabels): array {
            $status = $app->status instanceof ApplicationStatus ? $app->status->value : (string) $app->status;
            [$label, $badge] = $statusLabels[$status] ?? [$status, 'bg-slate-100 text-slate-600'];

            return [
                $app->id,
                e($app->application_no),
                e(trim($app->applicant_first_name . ' ' . $app->applicant_last_name)),
                e($app->institution?->name ?? '—'),
                '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold ' . $badge . '">' . $label . '</span>',
                e($app->address_text ?? '—'),
                $app->created_at?->format('d.m.Y') ?? '—',
                $app->id, // action column (show/edit links)
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => Application::count(),
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ]);
    }

    /**
     * TC Kimlik / Vergi No sansürleme: ilk 3 hane + yıldızlar + son 2 hane.
     * Örnek: 12345678901 → 123*******01
     */
    private function maskNationalId(string $id): string
    {
        $length = strlen($id);
        if ($length < 5) {
            return str_repeat('*', $length);
        }
        $visibleStart = 3;
        $visibleEnd = 2;
        $masked = $length - $visibleStart - $visibleEnd;

        return substr($id, 0, $visibleStart)
            . str_repeat('*', max($masked, 1))
            . substr($id, -$visibleEnd);
    }

    private function statusLabels(): array
    {
        return [
            'draft'                  => ['Taslak',                'bg-slate-100 text-slate-700'],
            'submitted'              => ['Ön Kazı Bekliyor',      'bg-sky-100 text-sky-700'],
            'pre_excavation_approved'=> ['Ön Kazı Onaylı',        'bg-cyan-100 text-cyan-700'],
            'priced'                 => ['Fiyatlandı',            'bg-indigo-100 text-indigo-700'],
            'awaiting_payment'       => ['Ödeme Bekliyor',        'bg-amber-100 text-amber-700'],
            'receipt_pending'        => ['Makbuz Bekliyor',       'bg-orange-100 text-orange-700'],
            'approved'               => ['Onaylandı',             'bg-emerald-100 text-emerald-700'],
            'rejected'               => ['Reddedildi',            'bg-red-100 text-red-700'],
            'licensed'               => ['Ruhsatlandı',           'bg-teal-100 text-teal-700'],
            'field_work'             => ['Saha Çalışması',        'bg-violet-100 text-violet-700'],
            'completed'              => ['Tamamlandı',            'bg-green-100 text-green-700'],
            'archived'               => ['Arşivlendi',            'bg-gray-200 text-gray-600'],
        ];
    }

    public function destroy(Application $application): \Illuminate\Http\JsonResponse
    {
        $this->authorize('delete', $application);

        AuditLogger::log(
            'application.delete',
            "Başvuru silindi: {$application->application_no}",
            'Application',
            $application->id
        );

        $application->delete();

        return response()->json(['message' => 'Başvuru silindi.']);
    }

    public function bulkDestroy(Request $request): \Illuminate\Http\JsonResponse
    {
        $ids = $request->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'Silinecek başvuru seçilmedi.'], 400);
        }

        $applications = Application::whereIn('id', $ids)->get();
        $count = 0;

        foreach ($applications as $application) {
            if (auth()->user()->can('delete', $application)) {
                AuditLogger::log(
                    'application.bulk_delete',
                    "Toplu silme: {$application->application_no}",
                    'Application',
                    $application->id
                );
                $application->delete();
                $count++;
            }
        }

        return response()->json(['message' => "{$count} başvuru silindi."]);
    }

    public function statusBadgeMeta(ApplicationStatus|string|null $status): array
    {
        $value = $status instanceof ApplicationStatus ? $status->value : (string) $status;

        return match ($value) {
            ApplicationStatus::Draft->value => ['label' => 'Taslak', 'class' => 'bg-slate-100 text-slate-700'],
            ApplicationStatus::Submitted->value => ['label' => 'Ön kazı bekliyor', 'class' => 'bg-sky-100 text-sky-700'],
            ApplicationStatus::PreExcavationApproved->value => ['label' => 'Ön kazı onaylı', 'class' => 'bg-cyan-100 text-cyan-700'],
            ApplicationStatus::Priced->value => ['label' => 'Fiyatlandı', 'class' => 'bg-indigo-100 text-indigo-700'],
            ApplicationStatus::AwaitingPayment->value => ['label' => 'Ödeme bekliyor', 'class' => 'bg-amber-100 text-amber-700'],
            ApplicationStatus::ReceiptPending->value => ['label' => 'Makbuz bekliyor', 'class' => 'bg-orange-100 text-orange-700'],
            ApplicationStatus::Approved->value => ['label' => 'Onaylandı', 'class' => 'bg-emerald-100 text-emerald-700'],
            ApplicationStatus::Licensed->value => ['label' => 'Ruhsatlı', 'class' => 'bg-green-100 text-green-700'],
            ApplicationStatus::FieldWork->value => ['label' => 'Saha işi', 'class' => 'bg-blue-100 text-blue-700'],
            ApplicationStatus::Completed->value => ['label' => 'Tamamlandı', 'class' => 'bg-teal-100 text-teal-700'],
            ApplicationStatus::Rejected->value => ['label' => 'Reddedildi', 'class' => 'bg-rose-100 text-rose-700'],
            ApplicationStatus::Archived->value => ['label' => 'Arşiv', 'class' => 'bg-zinc-100 text-zinc-700'],
            default => ['label' => $value !== '' ? str_replace('_', ' ', $value) : 'Bilinmiyor', 'class' => 'bg-slate-100 text-slate-700'],
        };
    }
}
