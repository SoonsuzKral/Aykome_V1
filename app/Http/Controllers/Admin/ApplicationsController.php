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
use App\Models\SurfaceType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use App\Models\PermitSetting;
use App\Services\ApplicationService;
use App\Services\AuditLogger;
use App\Services\DocumentRenderer;
use App\Services\DocumentTemplateService;
use App\Services\LicenseService;
use App\Services\MapDrawingService;
use App\Services\PricingService;
use App\Services\ProcessEngine;
use App\Services\SignatoryEngine;
use App\Services\TaskTransferService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
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
            'application_type' => trim((string) $request->query('application_type', '')),
        ];

        $query = Application::query()->with(['institution', 'creator'])->latest();

        // ── Data isolation ────────────────────────────────────────────────
        if ($user->hasRole('field-team')) {
            // Saha personeli: sadece kendisine atanmış görevlerdeki başvurular
            $query->whereHas('fieldTasks', fn ($q) => $q->where('assigned_to', $user->id));
        } elseif (! $user->isMunicipalityPersonel()) {
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

        if ($filters['institution_id'] !== '' && $user->isMunicipalityPersonel()) {
            $institutionId = (int) $filters['institution_id'];
            if ($institutionId > 0) {
                $query->where('institution_id', $institutionId);
            }
        }

        if ($filters['application_type'] !== '') {
            $query->where('application_type', $filters['application_type']);
        }

        return view('admin.applications.index', [
            'applications' => $query->paginate(15)->withQueryString(),
            'filters' => $filters,
            'statuses' => ApplicationStatus::cases(),
            'institutions' => $user->isMunicipalityPersonel()
                ? Institution::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Application::class);

        $user = $request->user();
        $isInstitutionUser = ! $user->isMunicipalityPersonel();

        $institutions = $isInstitutionUser
            ? Institution::query()->where('id', $user->institution_id)->get(['id', 'name', 'slug', 'color_code', 'is_municipality', 'tax_number', 'phone'])
            : Institution::query()->orderBy('name')->get(['id', 'name', 'slug', 'color_code', 'is_municipality', 'tax_number', 'phone']);

        $applicantPrefill = null;
        $institutionPrefill = null;
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

            $inst = $user->institution;
            if ($inst && $inst->tax_number) {
                $institutionPrefill = [
                    'tax_number' => $inst->tax_number,
                    'phone' => $inst->phone ?? $user->phone ?? '',
                ];
            }
        }

        return view('admin.applications.create', [
            'institutions' => $institutions,
            'surfaceTypes' => \App\Models\SurfaceType::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'price_per_m2']),
            'googleMapsApiKey' => config('services.google_maps.api_key') ?: config('aykome.google_maps_api_key'),
            'isInstitutionUser' => $isInstitutionUser,
            'applicantPrefill' => $applicantPrefill,
            'institutionPrefill' => $institutionPrefill,
            'processes' => \App\Models\ProcessDefinition::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get(['id', 'name', 'description']),
        ]);
    }

    public function store(StoreApplicationRequest $request, ApplicationService $service): RedirectResponse
    {
        $validated = $request->validated();

        // ── Mevzuat güvenlik duvarı: kurum personeli vatandaş adına başvuru AÇAMAZ ──
        // Request'ten gelen applicant alanları tamamen görmezden gelinir;
        // zorunlu olarak oturum açan kullanıcının kendi bilgileri yazılır.
        $user = $request->user();
        if (! $user->isMunicipalityPersonel()) {
            $nationalId = preg_replace('/\D+/', '', (string) ($user->national_id ?? '')) ?: null;
            $nameParts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $validated['applicant_first_name'] = mb_convert_case((string) (array_shift($nameParts) ?: ''), MB_CASE_TITLE, 'UTF-8');
            $validated['applicant_last_name'] = mb_convert_case(trim(implode(' ', $nameParts)), MB_CASE_TITLE, 'UTF-8');
            $validated['applicant_national_id'] = $nationalId;
            $validated['tc_no'] = $nationalId;
            $validated['identity_no'] = $nationalId;
            $validated['applicant_phone'] = $user->phone ?? null;
        }

        // Soyad alanı formdan kaldırıldı; boşsa first_name kopyalansın (Ad Soyad tek alan)
        $validated['applicant_last_name'] ??= $validated['applicant_first_name'] ?? '';

        $validated['applicant_national_id'] = preg_replace('/\D+/', '', (string) ($validated['applicant_national_id'] ?? '')) ?: null;
        $validated['tc_no'] = preg_replace('/\D+/', '', (string) ($validated['tc_no'] ?? $validated['applicant_national_id'] ?? '')) ?: $validated['applicant_national_id'];
        $validated['identity_no'] = preg_replace('/\D+/', '', (string) ($validated['identity_no'] ?? $validated['applicant_national_id'] ?? '')) ?: $validated['applicant_national_id'];

        // Address components: JSON string → decode to array (model casts 'address_components' as 'array')
        $rawComponents = $validated['address_components_json'] ?? $validated['address_components'] ?? null;
        if (is_string($rawComponents) && $rawComponents !== '') {
            $decoded = json_decode($rawComponents, true);
            if (is_array($decoded)) {
                $validated['address_components'] = $decoded;
            }
        }
        unset($validated['address_components_json']);

        $application = $service->createDraft($request->user(), $validated);

        $this->handleDocumentUploads($request, $application);

        AuditLogger::log('application.create', "Yeni başvuru oluşturuldu: {$application->application_no}", 'Application', $application->id);
        return redirect()
            ->route('admin.applications.show', $application)
            ->with('success', 'Başvuru taslak olarak kaydedildi.');
    }

    /** EK RUHSAT Süreci — asıl başvurudan klon üretir. */
    public function createAdditionalPermit(Application $application)
    {
        $this->authorize('update', $application);

        // Ek Ruhsat yalnızca merkez belediye / vatandaş (kurum olmayan) için çıkarılır.
        $isInstitution = $application->institution_id && ! $application->institution?->is_municipality;
        abort_if($isInstitution, 403, 'Ek Ruhsat yalnızca merkez belediye / vatandaş başvuruları için oluşturulabilir.');

        $additional = app(\App\Services\ApplicationService::class)
            ->createAdditionalPermit(auth()->user(), $application);

        AuditLogger::log(
            'additional_permit.created',
            "Ek Ruhsat oluşturuldu: {$additional->application_no} (asıl: {$application->application_no})",
            'Application',
            $additional->id,
        );

        return redirect()
            ->route('admin.applications.edit', $additional)
            ->with('success', 'Ek Ruhsat başvurusu oluşturuldu. Metraj ve fiyat bilgilerini işleyin.');
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

        if (! $request->user()->isMunicipalityPersonel()) {
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

        if (! $request->user()->isMunicipalityPersonel()) {
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
            'assignee',
            'timelineLogs.user',
            'history.user',
            'fieldTasks.assignee',
            'receipts.uploader',
            'receipts.reviewer',
            'documents',
            'extraPermits',
        ]);

        // Görevi devret listesi → tüm Eyyübiye merkez kullanıcıları
        // (memur/şef/admin/makam vb. + saha personeli), alt kurum kullanıcıları hariç.
        $fieldUsers = User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->role([
                    'super-admin',
                    'municipality-admin',
                    'municipality-staff',
                    'municipality-buro',
                    'municipality-sef',
                    'municipality-mudur',
                    'municipality-makam',
                    'field-team',
                ]);
            })
            ->where(function ($q) {
                $q->whereNull('institution_id')
                    ->orWhereHas('institution', fn ($institutionQuery) => $institutionQuery->where('is_municipality', true));
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $surfaceTypes = SurfaceType::query()->orderBy('name')->get(['id', 'name', 'price_per_m2']);

        $institutions = $request->user()->can('transferToInstitution', $application)
            ? Institution::query()->where('is_municipality', false)->whereKeyNot($application->institution_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        $engine = app(ProcessEngine::class);
        $currentStep = $engine->currentStep($application);

        return view('admin.applications.show', [
            'application' => $application,
            'fieldUsers' => $fieldUsers,
            'surfaceTypes' => $surfaceTypes,
            'institutions' => $institutions,
            'googleMapsApiKey' => config('services.google_maps.api_key') ?: config('aykome.google_maps_api_key'),
            'processCurrentStep' => $currentStep,
            'processCurrentStepIsFinal' => $currentStep ? $engine->isLastStep($application) : false,
            'approvalLog' => $application->approval_log ?? [],
            'can' => [
                'update' => $request->user()->can('update', $application),
                'approve_pre_excavation' => $request->user()->can('approvePreExcavation', $application),
                'approve_staff' => $request->user()->can('approveStaff', $application),
                'approve_director' => $request->user()->can('approveDirector', $application),
                'approve_vice_mayor' => $request->user()->can('approveViceMayor', $application),
                'approve_current' => $request->user()->can('approvePreExcavation', $application)
                    && $currentStep !== null
                    && $engine->roleCanApproveStep($currentStep, $request->user()),
                // GÖREV 3 — E-İmzala butonu yetki izolasyonu. Belediye personeli süreç
                // adımında rolü varsa (veya adım e-imza config'liyse canSignStep), alt
                // kurum kullanıcısı kendi başvurusunda update yetkisine sahipse görünür.
                'e_imza' => $currentStep !== null
                    && ($engine->roleCanApproveStep($currentStep, $request->user())
                        || $engine->canSignStep($currentStep, $request->user())
                        || (!$request->user()->isMunicipalityPersonel() && $request->user()->can('update', $application))),
                'approve_price' => $request->user()->can('approvePrice', $application),
                'approve_receipt' => $request->user()->can('approveReceipt', $application),
                'transfer' => $request->user()->can('transferTask', $application),
                'transfer_institution' => $request->user()->can('transferToInstitution', $application),
                'reject_receipt' => $request->user()->can('approveReceipt', $application),
            ],
        ]);
    }

    public function edit(Request $request, Application $application): View
    {
        $this->authorize('update', $application);

        $user = $request->user();
        $application->loadMissing(['institution:id,name,slug,color_code,is_municipality,tax_number,phone', 'excavationAreas', 'documents']);
        $application->load(['surfaceLines.surfaceType']);
        $area = $application->excavationAreas->sortByDesc('updated_at')->first();

        $institutions = $user->isMunicipalityPersonel()
            ? Institution::query()->orderBy('name')->get(['id', 'name', 'slug', 'color_code', 'is_municipality', 'tax_number', 'phone'])
            : Institution::query()->where('id', $user->institution_id)->get(['id', 'name', 'slug', 'color_code', 'is_municipality', 'tax_number', 'phone']);

        $isInstitutionUser = ! $user->isMunicipalityPersonel();
        $nameParts = preg_split('/\s+/', trim((string) $user->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $applicantPrefill = [
            'first_name' => mb_convert_case((string) (array_shift($nameParts) ?: ''), MB_CASE_TITLE, 'UTF-8'),
            'last_name' => mb_convert_case(trim(implode(' ', $nameParts)), MB_CASE_TITLE, 'UTF-8'),
            'national_id' => $user->national_id ?? '',
            'national_id_masked' => $user->national_id ? str_repeat('*', 8) . substr($user->national_id, -3) : '',
            'phone' => $user->phone ?? '',
        ];
        $institutionPrefill = $user->institution ? [
            'tax_number' => $user->institution->tax_number ?? '',
            'name' => $user->institution->name ?? '',
            'phone' => $user->institution->phone ?? '',
        ] : null;

        return view('admin.applications.edit', [
            'application' => $application,
            'institutions' => $institutions,
            'surfaceTypes' => \App\Models\SurfaceType::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'price_per_m2']),
            'googleMapsApiKey' => config('services.google_maps.api_key') ?: config('aykome.google_maps_api_key'),
            'isInstitutionUser' => $isInstitutionUser,
            'applicantPrefill' => $applicantPrefill,
            'institutionPrefill' => $institutionPrefill,
            'drawing' => [
                'polygon_geojson' => $area?->polygon_geojson,
                'total_area_m2' => $area?->total_area_m2 ?? $application->total_area_m2,
                'center_lat' => $area?->center_lat,
                'center_lng' => $area?->center_lng,
            ],
            'surfaceLinesData' => $application->surfaceLines->map(fn ($sl) => [
                'id' => $sl->id,
                'surface_type_id' => $sl->surface_type_id,
                'surface_type_name' => $sl->surfaceType?->name ?? '',
                'price_per_m2' => (float) ($sl->surfaceType?->price_per_m2 ?? 0),
                'width_m' => (float) ($sl->width_m ?? 0),
                'length_m' => (float) ($sl->length_m ?? 0),
                'quantity' => (float) ($sl->quantity ?? 0),
                'amount' => (float) ($sl->amount ?? 0),
                'address' => $sl->address ?? '',
            ])->values()->toArray(),
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
        foreach (['total_area_m2', 'deposit_amount', 'excavation_amount'] as $field) {
            if ($request->has($field) && is_string($request->input($field))) {
                $val = $request->input($field);
                $val = str_replace('.', '', $val);
                $val = str_replace(',', '.', $val);
                $request->merge([$field => $val !== '' ? $val : null]);
            }
        }

        // Normalize surface_lines decimals
        if ($request->has('surface_lines') && is_array($request->input('surface_lines'))) {
            $normalized = [];
            foreach ($request->input('surface_lines') as $index => $line) {
                if (! is_array($line)) continue;
                foreach (['width_m', 'length_m', 'quantity'] as $f) {
                    if (isset($line[$f]) && is_string($line[$f])) {
                        $line[$f] = str_replace(',', '.', $line[$f]);
                    }
                }
                $normalized[$index] = $line;
            }
            $request->merge(['surface_lines' => $normalized]);
        }

        $data = $request->validate([
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'applicant_first_name' => ['nullable', 'string', 'max:255'],
            'applicant_last_name' => ['nullable', 'string', 'max:255'],
            'applicant_national_id' => ['nullable', 'string', 'max:20'],
            'tc_no' => ['nullable', 'string', 'max:20'],
            'identity_no' => ['nullable', 'string', 'max:20'],
            'applicant_phone' => ['nullable', 'string', 'max:32'],
            'project_code' => ['nullable', 'string', 'max:100'],
            'application_type' => ['nullable', 'string', 'in:basvuru,ariza', 'max:20'],
            'excavation_reason' => ['nullable', 'string', 'max:500'],
            'work_type' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'address_text' => ['nullable', 'string', 'max:500'],
            'address_components_json' => ['nullable', 'string'],
            'address_components' => ['nullable', 'string'],
            'polygon_geojson' => ['nullable', 'string'],
            'total_area_m2' => ['nullable', 'numeric', 'min:0'],
            'center_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'center_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'surface_lines' => ['nullable', 'array', 'min:1'],
            'surface_lines.*.surface_type_id' => ['required', 'integer', 'exists:surface_types,id'],
            'surface_lines.*.width_m' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'surface_lines.*.length_m' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'surface_lines.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'excavation_amount' => ['nullable', 'numeric', 'min:0'],
            'vice_mayor_name' => ['nullable', 'string', 'max:255'],
            'tesis_sorumlusu' => ['nullable', 'string', 'max:255'],
            'tesis_sorumlusu_adi' => ['nullable', 'string', 'max:255'],
            'duzenleyen_kisi' => ['nullable', 'string', 'max:255'],
            'mudur_adi' => ['nullable', 'string', 'max:255'],
            'mudur_unvani' => ['nullable', 'string', 'max:255'],
            'documents' => ['nullable', 'array'],
            'documents.*' => ['nullable', 'file', 'mimetypes:application/pdf,image/jpeg,image/png,image/jpg,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/webp,image/gif,image/bmp,image/tiff', 'max:51200'],
        ]);

        // Address components: JSON string → decode to array (model casts 'address_components' as 'array')
        $rawComponents = $data['address_components_json'] ?? $data['address_components'] ?? null;
        if (is_string($rawComponents) && $rawComponents !== '') {
            $decoded = json_decode($rawComponents, true);
            if (is_array($decoded)) {
                $data['address_components'] = $decoded;
            }
        }
        unset($data['address_components_json']);

        // Normalize national IDs
        if (! empty($data['applicant_national_id'])) {
            $data['applicant_national_id'] = preg_replace('/\D+/', '', $data['applicant_national_id']);
            $data['tc_no'] = $data['applicant_national_id'];
            $data['identity_no'] = $data['applicant_national_id'];
        }
        $data['applicant_last_name'] ??= $data['applicant_first_name'] ?? '';

        $user = $request->user();
        if (! $user->isMunicipalityPersonel()) {
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
            'applicant_first_name' => $data['applicant_first_name'] ?? $application->applicant_first_name,
            'applicant_last_name' => $data['applicant_last_name'] ?? $application->applicant_last_name,
            'applicant_national_id' => $data['applicant_national_id'] ?? $application->applicant_national_id,
            'tc_no' => $data['tc_no'] ?? $application->tc_no,
            'identity_no' => $data['identity_no'] ?? $application->identity_no,
            'applicant_phone' => $data['applicant_phone'] ?? $application->applicant_phone,
            'project_code' => $data['project_code'] ?? $application->project_code,
            'application_type' => $application->is_additional_permit ? 'ek_ruhsat' : ($data['application_type'] ?? $application->application_type),
            'excavation_reason' => $data['excavation_reason'] ?? $application->excavation_reason,
            'work_type' => $data['work_type'] ?? $application->work_type,
            'start_date' => $data['start_date'] ?? $application->start_date,
            'end_date' => $data['end_date'] ?? $application->end_date,
            'description' => $data['description'] ?? $application->description,
            'address_text' => $data['address_text'] ?? $application->address_text,
            'address_components' => $data['address_components'] ?? $application->address_components,
            'total_area_m2' => $totalAreaM2 ?? ($data['total_area_m2'] ?? $application->total_area_m2),
            'vice_mayor_name' => $data['vice_mayor_name'] ?? $application->vice_mayor_name,
            'tesis_sorumlusu' => $data['tesis_sorumlusu'] ?? $application->tesis_sorumlusu,
            'tesis_sorumlusu_adi' => $data['tesis_sorumlusu_adi'] ?? $application->tesis_sorumlusu_adi,
            'duzenleyen_kisi' => $data['duzenleyen_kisi'] ?? $application->duzenleyen_kisi,
            'mudur_adi' => $data['mudur_adi'] ?? $application->mudur_adi,
            'mudur_unvani' => $data['mudur_unvani'] ?? $application->mudur_unvani,
        ]);

        $this->handleDocumentUploads($request, $application);

        if (! empty($data['surface_lines']) && is_array($data['surface_lines'])) {
            $pricingService->upsertSurfaceLines($application, $data['surface_lines']);
        }

        // Always recalculate totals to keep DB computed fields in sync
        $pricingService->recalculateTotals($application);

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

        $files = $request->file('documents');
        if (! is_array($files) && ! $files instanceof \Illuminate\Http\UploadedFile) {
            return;
        }
        $files = is_array($files) ? $files : [$files];

        $existingCount = $application->documents()->count();

        foreach ($files as $file) {
            if (! $file || ! $file->isValid()) {
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

        // GÜVENLİK: Yeni yükleme sonrası eski belgelerin silinmediğini doğrula
        $newCount = $application->documents()->count();
        if ($newCount < $existingCount) {
            \Illuminate\Support\Facades\Log::warning('[handleDocumentUploads] Beklenmeyen belge silinmesi tespit edildi', [
                'application_id' => $application->id,
                'existing_count' => $existingCount,
                'new_count' => $newCount,
            ]);
        }
    }

    public function submit(Request $request, Application $application, ApplicationService $service): RedirectResponse
    {
        if ($request->isMethod('GET')) {
            return redirect()->route('admin.applications.show', $application);
        }

        $this->authorize('update', $application);

        $service->submit($request->user(), $application);

        return back()->with('success', 'Başvuru belediyeye iletildi.');
    }

    public function approvePreExcavation(Request $request, Application $application, ApplicationService $service): RedirectResponse
    {
        $stage = $application->approval_stage ?? 'staff';

        $this->authorize(match ($stage) {
            'director'   => 'approveDirector',
            'vice_mayor' => 'approveViceMayor',
            default      => 'approveStaff',
        }, $application);

        $viceMayorName = $stage === 'vice_mayor' ? trim((string) $request->input('vice_mayor_name')) : null;

        // Başkan Yrd. adı boş bırakılırsa global makam ayarından çekilir;
        // ayar da yoksa boş string ile onay zorla ilerletilir (onay asla bloke olmaz).
        if ($stage === 'vice_mayor' && $viceMayorName === '') {
            $setting = SignatoryEngine::resolve('pre_permit', $application->institution_id, 'belediye_baskan_yardimcisi');
            $viceMayorName = $setting?->ad_soyad ?? '';
        }

        $service->advanceApproval($request->user(), $application, $viceMayorName);

        $message = match ($stage) {
            'staff'      => 'Onay alındı. Başvuru Müdür onayına gönderildi.',
            'director'   => 'Müdür onayı alındı. Başvuru Başkan Yardımcısı onayına gönderildi.',
            default      => 'Ön kazı izni onaylandı.',
        };

        AuditLogger::log('pre_excavation.approve', "Ön kazı onay akışı ilerledi: {$application->application_no} ({$stage})", 'Application', $application->id);
        return back()->with('success', $message);
    }

    /**
     * KATI ADIM KAPISI: Kurum, saha kazı çalışmalarını tamamladığını bildirir (Ping).
     * → durum 'excavation_completed'. İLERİ MODÜLLER AÇILMAZ; belediye manuel olarak açar.
     */
    public function completeFieldWork(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless(
            $request->user()->hasAnyRole(['municipality-sef', 'municipality-admin', 'municipality-makam', 'super-admin']),
            403,
            'Bu işlem yalnızca Aykome Birim Şefi yetkisiyle yapılır.'
        );

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless(
            in_array($currentStatus, ['pre_excavation_approved', 'pre_approved'], true),
            422,
            'Başvuru bu aşamada saha kazı tamamlamaya uygun değil.'
        );

        $application->update([
            'status' => \App\Enums\ApplicationStatus::ExcavationCompleted,
        ]);

        AuditLogger::log(
            'application.excavation_completed',
            "Saha çalışmaları tamamlandı: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', '✅ Saha çalışmaları tamamlandı. Belediye Saha Metraj modülünü açacak.');
    }

    /**
     * KATI ADIM KAPISI: Belediye "KAZI METRAJ MODÜLÜNÜ AÇ" tuşuna basar.
     * excavation_completed → metrage_pending (yalnızca belediye, yalnızca alt kurum).
     */
    public function openMetraj(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless($request->user()->isMunicipalityPersonel(), 403, 'Bu işlem yalnızca belediye yetkisiyle yapılır.');
        abort_unless($application->isInstitutionApplication(), 422, 'Bu akış yalnızca kurum başvurularında geçerlidir.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless($currentStatus === 'excavation_completed', 422, 'Saha Metraj modülü bu aşamada açılamaz.');

        $application->update(['status' => \App\Enums\ApplicationStatus::MetragePending]);

        AuditLogger::log(
            'application.metrage_opened',
            "Belediye Saha Metraj modülünü açtı: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', '🔓 Saha Metraj modülü açıldı.');
    }

    /**
     * KATI ADIM KAPISI: Belediye metraj formunu doldurup Kuruma gönderir.
     * metrage_pending/metrage_revision → metrage_sent (Kurumda).
     * ALT KURUM Step 3'ü YALNIZCA bu andan itibaren görür (gecikmeli visibility).
     */
    public function sendMetrageToInstitution(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless($request->user()->isMunicipalityPersonel(), 403, 'Bu işlem yalnızca belediye yetkisiyle yapılır.');
        abort_unless($application->isInstitutionApplication(), 422, 'Bu akış yalnızca kurum başvurularında geçerlidir.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless(in_array($currentStatus, ['metrage_pending', 'metrage_revision'], true), 422, 'Metraj henüz kuruma gönderilemez.');

        $application->update(['status' => \App\Enums\ApplicationStatus::MetrageSent]);

        AuditLogger::log(
            'application.metrage_sent_institution',
            "Belediye Saha Metrajı kurum onayına gönderdi: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', 'Metraj kurum onayına gönderildi.');
    }

    /**
     * KATI ADIM KAPISI / PİNG-PONG: Kurum metrajı onaylar → metrage_approved.
     * Belediyenin tepesindeki "TAHAKKUK AÇ" kilidi böylece belirir.
     */
    public function approveMetrage(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless(! $request->user()->isMunicipalityPersonel() && $application->institution_id, 403, 'Bu işlem kurum tarafından yapılır.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless(in_array($currentStatus, ['metrage_sent'], true), 422, 'Metraj şu anda onaylanamaz.');

        $application->update(['status' => \App\Enums\ApplicationStatus::MetrageApproved]);

        AuditLogger::log(
            'application.metrage_approved',
            "Kurum Saha Metrajı onayladı: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', '✅ Kazı metraj formu onaylandı.');
    }

    /**
     * KATI ADIM KAPISI / PİNG-PONG: Kurum metrajı kabul etmez, belediyeye geri gönderir.
     * → metrage_revision (belediye yeniden düzenler). Zorunlu açıklama timeline'a işlenir.
     */
    public function rejectMetrage(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless(! $request->user()->isMunicipalityPersonel() && $application->institution_id, 403, 'Bu işlem kurum tarafından yapılır.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless(in_array($currentStatus, ['metrage_sent'], true), 422, 'Metraj şu anda geri gönderilemez.');

        $request->validate([
            'reject_note' => ['required', 'string', 'max:1000'],
        ]);
        $note = trim($request->input('reject_note'));

        $application->update(['status' => \App\Enums\ApplicationStatus::MetrageRevision]);

        $application->timelineLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'application.metrage_rejected',
            'meta' => ['note' => $note],
            'message' => 'Alt Kurum metrajı şu nedenle kabul etmedi: '.$note,
        ]);

        AuditLogger::log(
            'application.metrage_rejected',
            "Kurum metrajı reddetti: {$application->application_no} — {$note}",
            'Application',
            $application->id
        );

        return back()->with('success', '❌ Metraj belediyeye geri gönderildi (revizyon).');
    }

    /**
     * KATI ADIM KAPISI: Belediye "TAHAKKUK VE MAKBUZ MODÜLÜNÜ AÇ" tuşuna basar.
     * metrage_approved → tahakkuk_pending (Step 4 iki tarafa açılır).
     */
    public function openTahakkuk(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless($request->user()->isMunicipalityPersonel(), 403, 'Bu işlem yalnızca belediye yetkisiyle yapılır.');
        abort_unless($application->isInstitutionApplication(), 422, 'Bu akış yalnızca kurum başvurularında geçerlidir.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless($currentStatus === 'metrage_approved', 422, 'Tahakkuk & Makbuz modülü bu aşamada açılamaz.');

        $application->update(['status' => \App\Enums\ApplicationStatus::TahakkukPending]);

        AuditLogger::log(
            'application.tahakkuk_opened',
            "Belediye Tahakkuk & Makbuz modülünü açtı: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', '🔓 Tahakkuk & Makbuz modülü açıldı.');
    }

    /**
     * KATI ADIM KAPISI / SON YETKİ: Belediye "RUHSAT MODÜLÜNÜ AÇ" tuşuna basar.
     * payment_completed / approved → licensed + ruhsat PDF üretilir (Step 6 render edilir).
     * GÖREV 4: Taahhütname (taahhutname_sent) gönderilir gönderilmez Ruhsat hazırdır —
     * alt kurum onayı OLMADIĞI için belediye aynı anda açar.
     */
    public function openRuhsat(Request $request, Application $application, LicenseService $licenseService): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless($request->user()->isMunicipalityPersonel(), 403, 'Bu işlem yalnızca belediye yetkisiyle yapılır.');
        abort_unless($application->isInstitutionApplication(), 422, 'Bu akış yalnızca kurum başvurularında geçerlidir.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless(in_array($currentStatus, ['payment_completed', 'approved', 'taahhutname_sent'], true), 422, 'Ruhsat modülü bu aşamada açılamaz.');

        $result = $licenseService->generateExcavationPermitPdf($application);

        $application->update([
            'status' => \App\Enums\ApplicationStatus::Licensed,
            'licensed_at' => now(),
            'license_document_path' => $result['path'] ?? $application->license_document_path,
            'approval_status' => 'licensed',
        ]);

        AuditLogger::log(
            'application.ruhsat_opened',
            "Belediye Ruhsat modülünü açtı, ruhsat PDF üretildi: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', '🔓 Ruhsat modülü açıldı. Ruhsat PDF üretildi.');
    }

    /**
     * KATI ADIM KAPISI / GÖREV 4: Belediye imzalı Tahakkuk & Makbuz evrakını kuruma gönderir.
     * tahakkuk_pending → tahakkuk_sent (Alt kurum Step 4'ü yalnızca bu andan itibaren görür).
     */
    public function sendTahakkukToInstitution(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless($request->user()->isMunicipalityPersonel(), 403, 'Bu işlem yalnızca belediye yetkisiyle yapılır.');
        abort_unless($application->isInstitutionApplication(), 422, 'Bu akış yalnızca kurum başvurularında geçerlidir.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless($currentStatus === 'tahakkuk_pending', 422, 'Tahakkuk & Makbuz evrakı bu aşamada kuruma gönderilemez.');

        $application->update(['status' => \App\Enums\ApplicationStatus::TahakkukSent]);

        AuditLogger::log(
            'application.tahakkuk_sent_institution',
            "Belediye Tahakkuk & Makbuz evrakını kuruma gönderdi: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', 'Tahakkuk & Makbuz evrakı kuruma gönderildi.');
    }

    /**
     * KATI ADIM KAPISI / GÖREV 5: Belediye "TAAHHÜTNAME MODÜLÜNÜ AÇ" tuşuna basar.
     * payment_completed/approved → taahhutname_pending (yalnızca belediye görür; kuruma gizli).
     */
    public function openTaahhutname(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless($request->user()->isMunicipalityPersonel(), 403, 'Bu işlem yalnızca belediye yetkisiyle yapılır.');
        abort_unless($application->isInstitutionApplication(), 422, 'Bu akış yalnızca kurum başvurularında geçerlidir.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless(in_array($currentStatus, ['payment_completed', 'approved'], true), 422, 'Taahhütname modülü bu aşamada açılamaz.');

        $application->update(['status' => \App\Enums\ApplicationStatus::TaahhutnamePending]);

        AuditLogger::log(
            'application.taahhutname_opened',
            "Belediye Taahhütname modülünü açtı: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', '🔓 Taahhütname modülü açıldı.');
    }

    /**
     * KATI ADIM KAPISI / GÖREV 5 / GÖREV 4: Belediye taahhütnameyi kuruma gönderir.
     * taahhutname_pending → taahhutname_sent (Alt kurum Step 5'i yalnızca bu andan itibaren görür).
     */
    public function sendTaahhutnameToInstitution(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless($request->user()->isMunicipalityPersonel(), 403, 'Bu işlem yalnızca belediye yetkisiyle yapılır.');
        abort_unless($application->isInstitutionApplication(), 422, 'Bu akış yalnızca kurum başvurularında geçerlidir.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless($currentStatus === 'taahhutname_pending', 422, 'Taahhütname bu aşamada kuruma gönderilemez.');

        $application->update(['status' => \App\Enums\ApplicationStatus::TaahhutnameSent]);

        AuditLogger::log(
            'application.taahhutname_sent_institution',
            "Belediye Taahhütnameyi kuruma gönderdi: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', 'Taahhütname kuruma gönderildi.');
    }

    /**
     * KATI ADIM KAPISI / GÖREV 4 / SON TAKDİM: Belediye imzalı Ruhsat belgesini kuruma gönderir.
     * licensed (belediye hazırlığı) → ruhsat_sent (Alt kurum Step 6'yı yalnızca bu andan itibaren görür).
     */
    public function sendRuhsatToInstitution(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        abort_unless($request->user()->isMunicipalityPersonel(), 403, 'Bu işlem yalnızca belediye yetkisiyle yapılır.');
        abort_unless($application->isInstitutionApplication(), 422, 'Bu akış yalnızca kurum başvurularında geçerlidir.');

        $currentStatus = $application->status instanceof \App\Enums\ApplicationStatus
            ? $application->status->value
            : (string) $application->status;

        abort_unless($currentStatus === 'licensed', 422, 'Ruhsat bu aşamada kuruma gönderilemez.');

        $application->update(['status' => \App\Enums\ApplicationStatus::RuhsatSent]);

        AuditLogger::log(
            'application.ruhsat_sent_institution',
            "Belediye imzalı Ruhsatı kuruma gönderdi: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', 'Ruhsat kuruma gönderildi.');
    }

    public function downloadPrePermit(Application $application)
    {
        $this->authorize('view', $application);
        if ($resp = $this->signedResponseOrNull($application, 'pre_permit')) {
            return $resp;
        }
        if ($html = DocumentTemplateService::renderFor('on_kazi', $application)) {
            $html = $this->lockForAltKurum($html, $application);
            return response($html)->header('Content-Type', 'text/html; charset=utf-8');
        }
        $application->load(['institution', 'creator']);
        $settings = PreExcavationPermitSetting::first();

        $signatories = app(\App\Services\SignerPlacementService::class)
            ->yerlesimHazirla($application, 'pre_permit');

        $data = [
            'application' => $application,
            'belediye' => 'EYYÜBİYE BELEDİYE BAŞKANLIĞI',
            'mudurluk' => 'Fen İşleri Müdürlüğü',
            'sayi' => 'E-' . ($settings->document_prefix ?? '18790261') . '-' . str_pad($application->id, 6, '0', STR_PAD_LEFT),
            'tarih' => $application->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
            'konu' => mb_strtoupper($application->description ?? 'Kazı İzni Hk.', 'UTF-8'),
            'kurum' => mb_strtoupper($application->institution?->name ?? 'KURUM', 'UTF-8'),
            'ilgi_tarih' => $application->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
            'ilgi_sayi' => str_pad($application->id, 7, '0', STR_PAD_LEFT),
            'metin' => self::buildPrePermitText($application),
            'imza_ad' => $signatories['belediye_baskan_yardimcisi']['ad_soyad'],
            'imza_unvan' => $signatories['belediye_baskan_yardimcisi']['unvan'],
            'takip_adresi' => 'https://www.turkiye.gov.tr/eyyubiye-belediyesi-ebys',
            'adres' => $settings->address ?? '',
            'bilgi_kisi' => $settings->signer_name ?? '',
            'telefon' => $settings->phone ?? '',
            'fax' => $settings->fax ?? '',
            'eposta' => $application->institution?->email ?? $settings->email ?? '-',
            'web' => $settings->website ?? '-',
            'kep_adresi' => $application->institution?->email ?? 'eyyubiye@hs03.kep.tr',
        ];

        $html = \Illuminate\Support\Facades\View::make('admin.pdf.pre_permit', $data)->render();
        $html = $this->lockForAltKurum($html, $application);
        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function downloadCoverLetter(Application $application)
    {
        $this->authorize('view', $application);
        if ($resp = $this->signedResponseOrNull($application, 'cover_letter')) {
            return $resp;
        }
        $application->load(['institution', 'creator', 'gisCizimleri.yolIliskileri', 'gisNoktalari']);

        $logoBase64 = $this->institutionLogoBase64($application);

        if ($html = DocumentTemplateService::renderFor('cover_letter', $application)) {
            if ($logoBase64 && str_contains($html, '<div class="a4-container">')) {
                $logoBlock = '<div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">'
                    . '<img src="' . $logoBase64 . '" alt="Kurum Logosu" style="max-height:85px;width:auto;">'
                    . '</div>';
                $html = str_replace('<div class="a4-container">', '<div class="a4-container">' . $logoBlock, $html);
            }
            // ALT KURUM KİLİDİ: Belediye onay/devralma sürecinde (draft/değilse) belge
            // contenteditable HTML olarak asla döndürülmez → salt-okunur render edilir.
            $html = $this->lockForAltKurum($html, $application);
            return response($html)->header('Content-Type', 'text/html; charset=utf-8');
        }

        $signerName = $application->creator?->name ?? 'Yetkili';
        $signerShort = mb_substr($signerName, 0, mb_strrpos($signerName, ' ') ?: mb_strlen($signerName));

        $data = [
            'logo_base64' => $logoBase64,
            'kurum' => mb_strtoupper($application->institution?->name ?? 'DİCLE ELEKTRİK DAĞITIM A.Ş.', 'UTF-8'),
            'kurum_alt' => 'Şanlıurfa Tesis Yöneticiliği',
            'sayi' => 'E-50005665001100-100-' . str_pad($application->id, 7, '0', STR_PAD_LEFT),
            'tarih' => $application->created_at?->format('d.m.Y') ?? now()->format('d.m.Y'),
            'konu' => mb_strtoupper($application->description ?? 'KAZI İZNİ', 'UTF-8'),
            'alici' => 'EYYÜBİYE BELEDİYE BAŞKANLIĞI',
            'alici_alt' => 'AYKOME ŞUBE MÜDÜRLÜĞÜ',
            'ilgi_metin' => 'Eybel Proje Anonim Şirketi Genel Müdürlüğü 27.04.2026 tarihli ve 3916463 sayılı yazısı.',
            'paragraflar' => self::buildCoverLetterParagraphs($application),
            'mahalleler' => self::buildCoverLetterStreets($application),
            'muhendis' => $application->applicant_first_name && $application->applicant_last_name
                ? mb_strtoupper(trim($application->applicant_first_name . ' ' . $application->applicant_last_name), 'UTF-8')
                : 'Kurum Yetkilisi',
            'telefon' => $application->creator?->phone ?? $application->applicant_phone ?? '',
            'kazı_miktari' => $application->total_area_m2 ?? '',
            'application' => $application,
        ];

        $html = \Illuminate\Support\Facades\View::make('admin.pdf.cover_letter', $data)->render();
        $html = $this->lockForAltKurum($html, $application);
        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function downloadRuhsat(Application $application)
    {
        $this->authorize('view', $application);
        if ($resp = $this->signedResponseOrNull($application, 'ruhsat')) {
            return $resp;
        }
        if ($html = DocumentTemplateService::renderFor('ruhsat', $application)) {
            return response($this->docResponseHtml($html, $application, 'ruhsat', 'ruhsat_sent', 'AÇIM RUHSATI (FR-290) — KURUM İMZA BÖLGESİ', '💾 Kurum İmzasını Kaydet'))->header('Content-Type', 'text/html; charset=utf-8');
        }
        $application->load(['institution', 'creator', 'surfaceLines.surfaceType']);

        // TEK MUHASEBE KAYNAĞI: Tutarlar Model accessor'larından gelir (calcFigures).
        // Controller içinde KDV/ruhsat harcı/keşif/teminat asla yeniden hesaplanmaz.
        $surfaceRows = [];
        foreach ($application->surfaceLines ?? [] as $sl) {
            if (! $sl->surfaceType) {
                continue;
            }
            $surfaceRows[] = [
                'ad' => $sl->surfaceType->name,
                'birim' => 'm2',
                'miktar' => number_format((float) ($sl->quantity ?? 0), 2, ',', '.'),
                'tutar' => number_format((float) ($sl->amount ?? 0), 2, ',', '.'),
            ];
        }

        $ruhsatHtml = \Illuminate\Support\Facades\View::make('admin.pdf.ruhsat', [
            'application' => $application,
            'surfaceRows' => $surfaceRows,
            'signatories' => app(\App\Services\SignerPlacementService::class)
                ->yerlesimHazirla($application, 'ruhsat'),
            'talep_sahibi' => mb_strtoupper(
                trim($application->tesis_sorumlusu ?? $application->institution?->tesis_sorumlusu_adi ?? 'Yetkili Görevli'),
                'UTF-8'
            ),
        ])->render();
        return response($this->docResponseHtml($ruhsatHtml, $application, 'ruhsat', 'ruhsat_sent', 'AÇIM RUHSATI (FR-290) — KURUM İMZA BÖLGESİ', '💾 Kurum İmzasını Kaydet'))->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function downloadMetraj(Application $application)
    {
        $this->authorize('view', $application);
        if ($resp = $this->signedResponseOrNull($application, 'metraj')) {
            return $resp;
        }
        if ($html = DocumentTemplateService::renderFor('metraj', $application, false)) {
            return response($this->docResponseHtml($html, $application, 'metraj', 'metrage_sent', 'KAZI METRAJ CETVELİ VE ONAY — KURUM İMZA BÖLGESİ', '💾 Kurum İmzasını Kaydet'))->header('Content-Type', 'text/html; charset=utf-8');
        }
        $application->load(['institution', 'creator', 'surfaceLines.surfaceType', 'gisCizimleri.yolIliskileri', 'gisNoktalari']);

        $rows = self::buildMetrajRows($application);
        $toplamM2 = 0;
        foreach ($rows as $r) {
            $toplamM2 += (float) str_replace(['.', ','], ['', '.'], $r['m2']);
        }

        $projeKodu = $application->project_code ?? '';
        $isAdi = $application->work_type ?? '';
        $combinedParts = [];
        if ($projeKodu !== '') {
            $combinedParts[] = $projeKodu;
        }
        if ($isAdi !== '') {
            $combinedParts[] = $isAdi;
        }

        $data = [
            'kurum' => mb_strtoupper($application->institution?->name ?? 'DİCLE ELEKTRİK DAĞITIM A.Ş. ŞANLIURFA İL MÜDÜRLÜĞÜ', 'UTF-8'),
            'birim' => 'PROJE TESİS YÖNETİCİLİĞİ',
            'alici' => 'EYYÜBİYE BELEDİYE BAŞKANLIĞI FEN İŞLERİ MÜDÜRLÜĞÜ AYKOME BİRİMİ',
            'signatories' => app(\App\Services\SignerPlacementService::class)
            ->yerlesimHazirla($application, 'metraj'),
            'proje_kodu' => implode(' / ', $combinedParts),
            'tarih' => now()->format('d.m.Y'),
            'rows' => $rows,
            'toplam_m2' => number_format($toplamM2, 2, ',', '.'),
            'ilce' => $application->district ?? '',
            'firma' => mb_strtoupper($application->institution?->name ?? 'KURUM', 'UTF-8'),
            'is_cinsi' => $application->description ?? '',
            'talep_sahibi' => mb_strtoupper(
                trim($application->tesis_sorumlusu ?? $application->institution?->tesis_sorumlusu_adi ?? 'Yetkili Görevli'),
                'UTF-8'
            ),
        ];

        $html = \Illuminate\Support\Facades\View::make('admin.pdf.metraj', $data)->render();
        return response($this->docResponseHtml($html, $application, 'metraj', 'metrage_sent', 'KAZI METRAJ CETVELİ VE ONAY — KURUM İMZA BÖLGESİ', '💾 Kurum İmzasını Kaydet'))->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function downloadTaahhutname(Application $application)
    {
        $this->authorize('view', $application);
        if ($resp = $this->signedResponseOrNull($application, 'taahhutname')) {
            return $resp;
        }
        $html = DocumentTemplateService::renderFor('taahhutname', $application);
        if ($html === null) {
            $application->load(['institution', 'creator']);
            $html = \Illuminate\Support\Facades\View::make('admin.pdf.taahhutname', ['application' => $application])->render();
        }

        return response(
            $this->taahhutnamePdfResponse($html, $application)
        )->header('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * TAAHHÜTNAME PDF GÖRÜNTÜLEME:
     * Belge salt-okunurdur (bak + yazdır). YALNIZCA alt kurumun "RUHSATI TESLİM ALAN"
     * imza hücresini doldurduğu imza adımında (taahhutname_sent) o hücre + kaydet/yazdır
     * barı açık kalır. Belediye dahil hiç kimse PDF görüntüleme üzerinden düzenlemez;
     * düzenleme yalnızca "✏️ Düzenle (Kaydet)" editöründe yapılır.
     */
    private function taahhutnamePdfResponse(string $html, Application $application): string
    {
        $isSignStep = $this->altKurumCanSign($application, 'taahhutname_sent');

        if ($isSignStep) {
            $html = DocumentTemplateService::readOnlyRender($html, true); // imza hücresi harici her şey kilitli
            $html = $this->normalizeSignEditable($html);
            $html = str_ireplace('</body>', $this->signatureSaveSnippet(
                $application,
                'taahhutname',
                'TAAHHÜTNAME — RUHSATI TESLİM ALAN İMZA BÖLGESİ',
                '💾 İmzayı Kaydet'
            ) . '</body>', $html);

            return $html;
        }

        // TAM SALT-OKUNUR görüntüleme (bak + yazdır) — belediye dahil herkes.
        return DocumentTemplateService::readOnlyView($html, false);
    }

    public function downloadTahakkuk(Application $application)
    {
        $this->authorize('view', $application);
        if ($resp = $this->signedResponseOrNull($application, 'tahakkuk')) {
            return $resp;
        }
        if ($html = DocumentTemplateService::renderFor('tahakkuk', $application)) {
            $html = $this->lockForAltKurum($html, $application);
            return response($html)->header('Content-Type', 'text/html; charset=utf-8');
        }
        $application->load(['institution', 'creator', 'surfaceLines.surfaceType']);

        // TEK MUHASEBE KAYNAĞI: Tutarlar Model accessor'larından gelir (calcFigures).
        // Controller/Blade içinde KDV/keşif/teminat asla yeniden hesaplanmaz.
        $tahakkukSignatories = app(\App\Services\SignerPlacementService::class)
            ->yerlesimHazirla($application, 'tahakkuk');

        $data = [
            'belediye' => 'EYYÜBİYE BELEDİYESİ',
            'mudurluk' => 'Fen İşleri Müdürlüğü',
            'birim' => 'AYKOME BİRİMİ',
            'altbaslik' => 'ALTYAPI TESİSİ AÇIM RUHSAT BEDELİ HESABI',
            'talep_sahibi' => mb_strtoupper($application->institution?->name ?? '', 'UTF-8'),
            'ilce' => $application->district ?? '',
            'adres' => ($application->project_code ?? '') . ' ' . ($application->district ?? ''),
            'firma' => mb_strtoupper($application->institution?->name ?? '', 'UTF-8'),
            'is_cinsi' => $application->description ?? '',
            'vergino' => $application->applicant_national_id ?? '',
            'metraj_satirlari' => self::buildMetrajSatirlari($application),
            'duzenleyen' => $tahakkukSignatories['onay_imzaci']['ad_soyad'],
            'mukellef' => mb_strtoupper(
                trim(($application->applicant_first_name ?? '') . ' ' . ($application->applicant_last_name ?? '') ?: 'YETKİLİ'),
                'UTF-8'
            ),
            'application' => $application,
            'aciklama' => '',
        ];

        $html = \Illuminate\Support\Facades\View::make('admin.pdf.tahakkuk', $data)->render();
        $html = $this->lockForAltKurum($html, $application);
        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function downloadTahsilatFisi(Application $application)
    {
        $this->authorize('view', $application);
        if ($resp = $this->signedResponseOrNull($application, 'makbuz')) {
            return $resp;
        }
        if ($html = DocumentTemplateService::renderFor('tahsilat_fisi', $application)) {
            $html = $this->lockForAltKurum($html, $application);
            return response($html)->header('Content-Type', 'text/html; charset=utf-8');
        }
        $application->load(['institution', 'creator', 'surfaceLines.surfaceType']);

        // TEK MUHASEBE KAYNAĞI: Tüm tutarlar Model accessor'larından okunur.
        $app = $application;

        $surfaceRows = [];
        foreach ($app->surfaceLines ?? [] as $sl) {
            if (! $sl->surfaceType) {
                continue;
            }
            $surfaceRows[] = [
                'ad' => $sl->surfaceType->name,
                'birim' => 'm2',
                'miktar' => number_format((float) ($sl->quantity ?? 0), 2, ',', '.'),
                'birim_fiyat' => number_format((float) ($sl->surfaceType->price_per_m2 ?? 0), 2, ',', '.'),
                'tutar' => number_format((float) ($sl->amount ?? 0), 2, ',', '.'),
            ];
        }

        if (empty($surfaceRows)) {
            $surfaceRows = self::buildMetrajSatirlari($app);
        }

        $data = [
            'belediye' => 'EYYÜBİYE BELEDİYESİ',
            'mudurluk' => 'Fen İşleri Müdürlüğü',
            'birim' => 'AYKOME BİRİMİ',
            'altbaslik' => 'KAZI İZNİ TAHSİLAT FİŞİ',
            'fis_no' => 'TF-' . $app->application_no,
            'tarih' => now()->format('d.m.Y'),
            'talep_sahibi' => mb_strtoupper(
                trim(($app->applicant_first_name ?? '') . ' ' . ($app->applicant_last_name ?? '') ?: 'YETKİLİ'),
                'UTF-8'
            ),
            'basvuru_no' => $app->application_no,
            'adres' => ($app->project_code ?? '') . ' ' . ($app->address_text ?? ''),
            'ilce' => $app->district ?? 'EYYÜBİYE',
            'is_adi' => trim(
                ($app->project_code ? 'Kod: ' . $app->project_code . ' / ' : '') . ($app->work_type ? 'İş Cinsi: ' . $app->work_type : '')
            ) ?: ($app->description ?? '—'),
            'vergino' => $app->applicant_national_id ?? '—',
            'metraj_satirlari' => $surfaceRows,
            'duzenleyen' => $app->creator?->name ?? 'Yetkili',
        ];

        $html = \Illuminate\Support\Facades\View::make('admin.pdf.tahsilat_fisi', $data)->render();
        $html = $this->lockForAltKurum($html, $application);
        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function saveReceiptInfo(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'ztb_receipt_info' => ['nullable', 'string', 'max:255'],
            'deposit_receipt_info' => ['nullable', 'string', 'max:255'],
            'taahhutname_notu' => ['nullable', 'string', 'max:1000'],
        ]);

        $application->update($validated);

        AuditLogger::log('receipt_info.save', "Makbuz/taahhütname bilgileri kaydedildi: {$application->application_no}", 'Application', $application->id);

        return back()->with('success', 'Bilgiler kaydedildi.');
    }

    public function approvePrice(Request $request, Application $application, ApplicationService $service): RedirectResponse
    {
        if ($request->isMethod('GET')) {
            return redirect()->route('admin.applications.show', $application);
        }

        $this->authorize('approvePrice', $application);

        $service->approvePrice($request->user(), $application);

        AuditLogger::log('price.approve', "Fiyat onaylandı: {$application->application_no}", 'Application', $application->id);
        return back()->with('success', 'Fiyat onayı verildi.');
    }

    public function approveReceipt(Request $request, Application $application, ApplicationService $service, LicenseService $licenseService): RedirectResponse
    {
        if ($request->isMethod('GET')) {
            return redirect()->route('admin.applications.show', $application);
        }

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

    public function transferApplication(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
            'transfer_reason' => 'nullable|string|max:500',
        ]);

        $assignee = User::query()->findOrFail($validated['assigned_to']);
        $oldAssignee = $application->assignee;

        $application->update([
            'assigned_to' => $assignee->id,
        ]);

        AuditLogger::log('application.transfer', "Başvuru devredildi: {$application->application_no} → {$assignee->name}", 'Application', $application->id);

        return back()->with('success', "Başvuru {$assignee->name} kullanıcısına devredildi.");
    }

    public function transferToInstitution(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('transferToInstitution', $application);

        $validated = $request->validate([
            'institution_id' => 'required|exists:institutions,id',
            'transfer_reason' => 'nullable|string|max:500',
        ]);

        $newInstitution = Institution::query()->findOrFail($validated['institution_id']);
        $oldInstitution = $application->institution;

        if ($application->institution_id === $newInstitution->id) {
            return back()->with('error', 'Başvuru zaten bu kuruma ait.');
        }

        $application->update([
            'institution_id' => $newInstitution->id,
        ]);

        $meta = [
            'old_institution' => $oldInstitution?->name,
            'new_institution' => $newInstitution->name,
            'reason' => $validated['transfer_reason'] ?? null,
        ];

        $application->timelineLogs()->create([
            'user_id' => $request->user()->id,
            'action' => 'institution.transferred',
            'meta' => $meta,
            'message' => "Başvuru {$oldInstitution?->name} kurumundan {$newInstitution->name} kurumuna devredildi.",
        ]);

        AuditLogger::log('application.transfer_institution', "Başvuru kuruma devredildi: {$application->application_no} → {$newInstitution->name}", 'Application', $application->id, $meta);

        return back()->with('success', "Başvuru {$newInstitution->name} kurumuna devredildi.");
    }

    public function downloadLicense(Request $request, Application $application)
    {
        $this->authorize('view', $application);

        if ($resp = $this->signedResponseOrNull($application, 'ruhsat')) {
            return $resp;
        }

        if (! $application->license_document_path || ! Storage::disk('local')->exists($application->license_document_path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($application->license_document_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="ruhsat-'.$application->application_no.'.pdf"',
            ]
        );
    }

    /**
     * Generate the payment/cashier receipt PDF (Tahsilat Makbuzu).
     * Handed to the citizen to take to the cashier desk for payment.
     */
    public function generatePaymentReceipt(Application $application): Response
    {
        $this->authorize('view', $application);

        if ($resp = $this->signedResponseOrNull($application, 'makbuz')) {
            return $resp;
        }

        if ($html = DocumentTemplateService::renderFor('makbuz', $application, true, false)) {
            AuditLogger::log(
                'payment_receipt.downloaded',
                "Tahsilat makbuzu indirildi: {$application->application_no}",
                'Application',
                $application->id,
            );

            return Pdf::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->inline('tahsilat-makbuzu-' . $application->application_no . '.pdf');
        }

        $application->load(['institution']);

        // GÖREV 1+2: blade'deki print-bar/toolbar kalıntıları + Latin-1 fontlar temizlenir.
        $html = DocumentTemplateService::pdfCssEnjekte(
            \Illuminate\Support\Facades\View::make('admin.pdf.tahsilat_makbuzu', compact('application'))->render()
        );

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        AuditLogger::log(
            'payment_receipt.downloaded',
            "Tahsilat makbuzu indirildi: {$application->application_no}",
            'Application',
            $application->id,
        );

        return $pdf->inline('tahsilat-makbuzu-' . $application->application_no . '.pdf');
    }

    /**
     * Dynamically generate permit PDF using current PermitSettings (logo, signature, stamp).
     * Called from the "Ruhsat Belgesi Al" button — always fresh, reflects latest admin settings.
     */
    public function downloadPermitLive(Application $application): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('view', $application);

        if ($resp = $this->signedResponseOrNull($application, 'ruhsat')) {
            return $resp;
        }

        if ($html = DocumentTemplateService::renderFor('ruhsat', $application, true, false)) {
            return Pdf::loadHTML($html)
                ->setPaper('a4', 'portrait')
                ->inline('ruhsat-' . $application->application_no . '.pdf');
        }

        $application->load([
            'institution',
            'creator',
            'excavationAreas',
            'surfaceLines.surfaceType',
            'priceApprover',
            'receiptApprover',
        ]);

        // GÖREV 1+2: blade'deki print-bar/toolbar kalıntıları + Latin-1 fontlar temizlenir.
        $html = DocumentTemplateService::pdfCssEnjekte(
            \Illuminate\Support\Facades\View::make('admin.pdf.ruhsat', [
                'application' => $application,
                'signatories' => app(\App\Services\SignerPlacementService::class)
                    ->yerlesimHazirla($application, 'ruhsat'),
            ])->render()
        );

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        AuditLogger::log(
            'permit.downloaded',
            "Ruhsat belgesi indirildi: {$application->application_no}",
            'Application',
            $application->id,
        );

        return $pdf->inline('ruhsat-' . $application->application_no . '.pdf');
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

    public function updateSurfaceLines(Request $request, Application $application, PricingService $pricingService, \App\Services\DocumentSyncService $syncService): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'surface_lines' => 'nullable|array',
            'surface_lines.*.surface_type_id' => 'required|integer|exists:surface_types,id',
            'surface_lines.*.width_m' => 'nullable|numeric|min:0',
            'surface_lines.*.length_m' => 'nullable|numeric|min:0',
            'surface_lines.*.quantity' => 'required|numeric|min:0',
        ]);

        $pricingService->upsertSurfaceLines($application, $validated['surface_lines'] ?? []);
        $pricingService->recalculateTotals($application);

        // GÖREV 1 (SENKRON): Metraj formu kaydedildiği an mevcut ruhsat/tahakkuk/metraj
        // override'larındaki SAYI hücrelerini DB'den tazele (el metinleri korunur) —
        // böylece PDF çıktıları saniyesinde yeni/doğrulanmış tutarlara adapte olur.
        $syncService->hydrateAllOverrides($application);

        AuditLogger::log(
            'surface_lines.updated',
            "Zemin satırları güncellendi: {$application->application_no}",
            'Application',
            $application->id
        );

        return back()->with('success', 'Zemin satırları güncellendi.');
    }

    public function uploadSignedModuleDocument(Request $request, Application $application): JsonResponse
    {
        $this->authorize('update', $application);

        $request->validate([
            // GÖREV 5: "İmzalı Yükle" (signed-doc-upload) bileşeninin gönderdiği tüm modül anahtarları.
            // E-imza data-pdf-type ile birebir aynı sette olmalı; aksi halde upload 422 ile reddedilir.
            'module' => 'required|string|in:tahakkuk,metraj,ruhsat,taahhutname,pre_permit,makbuz,cover_letter_signed,on_kazi_signed,metraj_signed,taahhutname_imzali,ruhsat_teslim',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:20480',
        ]);

        $user = $request->user();
        $isMunicipality = $user->isMunicipalityPersonel()
            || empty($user->institution_id);
        $side = $isMunicipality ? 'belediye' : 'kurum';

        $file = $request->file('file');
        $module = $request->input('module');

        $year = now()->year;
        $appId = $application->id;
        $dir = "documents/{$year}-{$appId}";
        Storage::disk('public')->makeDirectory($dir);
        $storedPath = Storage::disk('public')->putFileAs(
            $dir,
            $file,
            sprintf('%s-%s_%s_imzali.%s', $year, $appId, $module, $file->getClientOriginalExtension())
        );

        if (!$storedPath) {
            return response()->json(['message' => 'Dosya kaydedilemedi.'], 500);
        }

        $moduleDocs = $application->module_documents ?? [];
        $moduleDocs[$module] = array_merge($moduleDocs[$module] ?? [], [
            "{$side}_path" => $storedPath,
            "{$side}_uploaded_at" => now()->toIso8601String(),
            "{$side}_uploaded_by" => $request->user()->id,
            'status' => "{$side}_signed",
        ]);

        $application->update(['module_documents' => $moduleDocs]);

        AuditLogger::log(
            'module_document.uploaded',
            "{$module} belgesi {$side} tarafından yüklendi: {$application->application_no}",
            'Application',
            $application->id
        );

        return response()->json(['message' => 'Belge başarıyla yüklendi.', 'path' => Storage::disk('public')->url($storedPath)]);
    }

    /**
     * İmzalı belgeyi öncelikli gösterir (file swap).
     * E-imza / ping-pong ile yüklenen imzalı dosya varsa SAF (temiz) dosya yerine O döner.
     */
    public function viewModuleDocument(Request $request, Application $application, string $module): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('view', $application);

        // Alt kurum işlem tabı anahtarlarını (on_kazi_signed → pre_permit, taahhutname_imzali → taahhutname vb.)
        // e-imza/upload mantığının yazdığı gerçek anahtara normalize et, orijinal anahtar fallback kalır.
        $normalized = match ($module) {
            'on_kazi_signed' => 'pre_permit',
            default => str_replace(['_signed', '_imzali', '_teslim'], '', $module),
        };
        $path = $application->moduleSignedPath($normalized) ?? $application->moduleSignedPath($module);
        if (! $path) {
            abort(404, 'İmzalı belge bulunamadı.');
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . basename($path) . '"',
        ]);
    }

    /**
     * Belge indirme istekleri için signed öncelik: imzalı dosya varsa onu döndür.
     */
    private function signedResponseOrNull(Application $application, string $module): ?\Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        if ($path = $application->moduleSignedPath($module)) {
            return response()->file(Storage::disk('public')->path($path), [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="imzali-' . basename($path) . '"',
            ]);
        }

        return null;
    }

    /**
     * ALT KURUM KİLİDİ (sunucu katmanı): Belediye personeli değilse ve başvuru
     * draft/rejected/revision statüsünde DEĞİLSE, renderFor() çıktısındaki TÜM
     * contenteditable atribütleri sökülür (DocumentTemplateService::readOnlyRender).
     * Böylece "Görüntüle / İndir (PDF)" butonu üst yazı dahil HİÇBİR belgeyi
     * düzenlenebilir HTML olarak alt kuruma servis etmez.
     */
    private function lockForAltKurum(string $html, Application $application): string
    {
        $status = $application->status;
        $raw = $status instanceof ApplicationStatus ? $status->value : (string) $status;

        if (auth()->user()->isMunicipalityPersonel()) {
            return $html;
        }

        if (in_array($raw, ['draft', 'rejected', 'revision'], true)) {
            return $html;
        }

        return DocumentTemplateService::readOnlyRender($html);
    }

    /**
     * Belge sayfasını sunarken: kilitle, rol bazlı imza hücresi editörlüğünü uygula ve
     * alt kurumun imza hücresini doldurabileceği imza aşamasında bir "Kaydet" üst barı
     * enjekte et. (Diğer tüm taraflarda/aşamalarda buton eklenmez — salt-okunur.)
     */
    private function docResponseHtml(string $html, Application $application, string $type, string $signStatus, string $barTitle, string $btnLabel): string
    {
        $html = $this->lockForAltKurum($html, $application);
        $html = $this->normalizeSignEditable($html);

        if ($this->altKurumCanSign($application, $signStatus)) {
            $html = str_ireplace('</body>', $this->signatureSaveSnippet($application, $type, $barTitle, $btnLabel) . '</body>', $html);
        }

        return $html;
    }

    /** Alt kurum + belirtilen imza aşamasında mı? */
    private function altKurumCanSign(Application $application, string $signStatus): bool
    {
        if (auth()->user()->isMunicipalityPersonel()) {
            return false;
        }
        $status = $application->status;
        $raw = $status instanceof ApplicationStatus ? $status->value : (string) $status;

        return $raw === $signStatus;
    }

    /**
     * DATA-SIGN EDİTÖRLÜĞÜNÜ GARANTİ ET: data-sign-editable="1" imza hücresi her iki taraf
     * (belediye + alt kurum) için contenteditable="true" olur. readOnlyRender alt kurumun
     * diğer tüm hücrelerini kilitleyip bu kutuyu açık bırakır; belediye ise hiç kilitlenmez.
     * Kaydedilen ortak şablondaki eski contenteditable değeri her render'a uyarlanır.
     */
    private function normalizeSignEditable(string $html): string
    {
        return (string) preg_replace_callback(
            '/<([a-zA-Z][a-zA-Z0-9]*)\b([^>]*?)>/',
            function (array $m): string {
                if (! preg_match('/data-sign-editable/i', $m[2])) {
                    return $m[0];
                }

                $attrs = $m[2];
                if (preg_match('/\s+contenteditable\s*=/i', $attrs)) {
                    $attrs = preg_replace(
                        '/\s+contenteditable\s*=\s*["\'][^"\']*["\']/i',
                        ' contenteditable="true"',
                        $attrs
                    );
                } else {
                    $attrs .= ' contenteditable="true"';
                }

                return '<' . $m[1] . $attrs . '>';
            },
            $html
        );
    }

    /** Alt kurum "imza kaydet" üst barı + JS (metraj + taahhütname ortak). */
    private function signatureSaveSnippet(Application $application, string $type, string $barTitle, string $btnLabel): string
    {
        $saveUrl = route('admin.applications.edit-document.save', [$application, $type]);
        $token = csrf_token();
        $appNo = e($application->application_no);
        $date = now()->format('d.m.Y');
        $fn = 'docSigSave_' . $type;

        return <<<HTML
<style>
    #msig { position: fixed; top: 0; left: 0; right: 0; z-index: 999999;
            background: linear-gradient(135deg,#0f172a 0%,#1e293b 100%);
            color: #fff; display: flex; align-items: center; justify-content: space-between;
            padding: 12px 18px; box-shadow: 0 6px 18px rgba(0,0,0,.35);
            font-family: Arial, sans-serif; box-sizing: border-box; }
    #msig .t { display: flex; flex-direction: column; gap: 2px; }
    #msig .nm { font-size: 14px; font-weight: bold; letter-spacing: .4px; color: #f8fafc; }
    #msig .mt { font-size: 11px; color: #94a3b8; }
    #msig .sbtn { margin-left: 12px; background: #059669; color: #fff; border: none;
            padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: bold;
            cursor: pointer; box-shadow: 0 3px 10px rgba(5,150,105,.4); }
    #msig .sbtn:hover { background: #06724f; }
    #msig .sbtn:disabled { opacity: .55; cursor: default; }
    #msig .pbtn { margin-left: 8px; background: #2563eb; color: #fff; border: none;
            padding: 10px 18px; border-radius: 8px; font-size: 13px; font-weight: bold;
            cursor: pointer; box-shadow: 0 3px 10px rgba(37,99,235,.35); }
    #msig .pbtn:hover { background: #1d4ed8; }
    @media print { #msig { display: none !important; } body { padding-top: 0 !important; } }
    body { padding-top: 66px !important; }
</style>
<div id="msig">
    <div class="t">
        <div class="nm">{$barTitle}</div>
        <div class="mt">Başvuru No: {$appNo} &nbsp;&bull;&nbsp; Tarih: {$date}</div>
    </div>
    <div style="display:flex; align-items:center;">
        <button type="button" class="sbtn" id="msig-save" onclick="{$fn}()">{$btnLabel}</button>
        <button type="button" class="pbtn" onclick="window.print()">🖨️ Yazdır</button>
    </div>
</div>
<script>
function {$fn}() {
    var btn = document.getElementById('msig-save');
    if (btn) btn.disabled = true;
    var content = document.body.innerHTML;
    fetch('{$saveUrl}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{$token}' },
        body: JSON.stringify({ content_data: content })
    })
    .then(function (r) { if (!r.ok) throw new Error('Sunucu hatası: ' + r.status); return r.json(); })
    .then(function () {
        var b = document.getElementById('msig');
        if (b) b.style.background = '#059669';
        if (btn) btn.textContent = '✓ Kaydedildi';
        setTimeout(function () { if (btn) { btn.textContent = '{$btnLabel}'; btn.disabled = false; } }, 3000);
    })
    .catch(function (err) {
        if (btn) btn.disabled = false;
        alert('Kaydetme başarısız: ' + err.message);
    });
}
</script>
HTML;
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
        } elseif (! $user->isMunicipalityPersonel()) {
            $query->where('institution_id', $user->institution_id);
        }

        // Status filter (custom param)
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Institution filter (super-admin / municipality only)
        if ($instId = $request->input('institution_id')) {
            if ($user->isMunicipalityPersonel()) {
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

    public function cancel(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string|max:1000',
        ]);

        $application->update([
            'status' => \App\Enums\ApplicationStatus::Cancelled,
            'rejection_reason' => $validated['cancellation_reason'] ?? null,
        ]);

        AuditLogger::log('application.cancel', "Başvuru iptal edildi: {$application->application_no}", 'Application', $application->id);

        return back()->with('success', 'Başvuru iptal edildi.');
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
            ApplicationStatus::ExcavationCompleted->value => ['label' => 'Kazı tamamlandı', 'class' => 'bg-blue-100 text-blue-700'],
            ApplicationStatus::MetragePending->value => ['label' => 'Metraj açıldı', 'class' => 'bg-sky-100 text-sky-700'],
            ApplicationStatus::MetrageSent->value => ['label' => 'Metraj kurumda', 'class' => 'bg-indigo-100 text-indigo-700'],
            ApplicationStatus::MetrageRevision->value => ['label' => 'Metraj revizyon', 'class' => 'bg-rose-100 text-rose-700'],
            ApplicationStatus::MetrageApproved->value => ['label' => 'Metraj onaylı', 'class' => 'bg-emerald-100 text-emerald-700'],
            ApplicationStatus::TahakkukPending->value => ['label' => 'Tahakkuk & makbuz açıldı', 'class' => 'bg-indigo-100 text-indigo-700'],
            ApplicationStatus::TahakkukSent->value => ['label' => 'Tahakkuk & makbuz kurumda', 'class' => 'bg-indigo-100 text-indigo-700'],
            ApplicationStatus::TaahhutnamePending->value => ['label' => 'Taahhütname açıldı', 'class' => 'bg-amber-100 text-amber-700'],
            ApplicationStatus::TaahhutnameSent->value => ['label' => 'Taahhütname kurumda', 'class' => 'bg-amber-100 text-amber-700'],
            ApplicationStatus::RuhsatSent->value => ['label' => 'Ruhsat kuruma gönderildi', 'class' => 'bg-green-100 text-green-700'],
            ApplicationStatus::PaymentCompleted->value => ['label' => 'Ödeme tamamlandı', 'class' => 'bg-teal-100 text-teal-700'],
            ApplicationStatus::Approved->value => ['label' => 'Onaylandı', 'class' => 'bg-emerald-100 text-emerald-700'],
            ApplicationStatus::Licensed->value => ['label' => 'Ruhsatlı', 'class' => 'bg-green-100 text-green-700'],
            ApplicationStatus::FieldWork->value => ['label' => 'Saha işi', 'class' => 'bg-blue-100 text-blue-700'],
            ApplicationStatus::Completed->value => ['label' => 'Tamamlandı', 'class' => 'bg-teal-100 text-teal-700'],
            ApplicationStatus::Rejected->value => ['label' => 'Reddedildi', 'class' => 'bg-rose-100 text-rose-700'],
            ApplicationStatus::Archived->value => ['label' => 'Arşiv', 'class' => 'bg-zinc-100 text-zinc-700'],
            default => ['label' => $value !== '' ? str_replace('_', ' ', $value) : 'Bilinmiyor', 'class' => 'bg-slate-100 text-slate-700'],
        };
    }

    public static function buildPrePermitText(Application $app): string
    {
        return \App\Services\DocumentRenderer::prePermitMetin($app);
    }

    private static function buildCoverLetterParagraphs(Application $app): array
    {
        $proje = trim((string) ($app->project_code ?? ''));
        $yil = (int) date('Y');

        // Lokasyon adresi — GIS ilişkilerinden, yoksa address'in ilk satırından.
        $mahalle = '';
        $sokak = '';
        $cizim = $app->gisCizimleri?->first();
        $yol = $cizim?->yolIliskileri?->first();
        if ($yol) {
            $mahalle = mb_strtoupper(trim((string) ($yol->mahalle ?? '')), 'UTF-8');
            $sokak = mb_strtoupper(trim((string) ($yol->yol_adi ?? '')), 'UTF-8');
        }
        if (! $mahalle && ! empty($app->address_text)) {
            $mahalle = mb_strtoupper(trim(explode("\n", $app->address_text)[0]), 'UTF-8');
        }

        $isAdi = mb_strtoupper(trim((string) ($app->work_type ?? $app->description ?? '')), 'UTF-8');
        $yuklenici = mb_strtoupper(trim((string) ($app->institution?->name ?? '')), 'UTF-8');

        $projeMetni = $proje !== '' ? " {$proje} pyp referans numarasıyla" : '';
        $mahalleMetni = $mahalle !== '' ? " {$mahalle}" : '';
        $sokakMetni = $sokak !== '' ? ", {$sokak}" : '';
        $isMetni = $isAdi !== '' ? " {$isAdi}" : '';
        $yukleniciMetni = $yuklenici !== '' ? "{$yuklenici} firmasının taahhüdünde kalmıştır" : 'taahhüdünde kalmıştır';

        return [
            'İlgi sayılı yazınız ile; Şirketimizden kazı izni sokaklarının mahalle isimleri güncellenmiştir.',
            "Şirketimiz {$yil} yılı yatırım programında{$projeMetni} yer alan ŞANLIURFA İLİ
            EYYÜBİYE İLÇESİ{$mahalleMetni}{$sokakMetni}{$isMetni} altyapı tesisi açım işinin ihale
            süreci tamamlanmış olup, söz konusu iş {$yukleniciMetni}. Eyyübiye Belediyesi sorumluluğunda
            bulunan cadde ve sokakların kazı izinleri belediyenizce verilmesi gerekmektedir.",
            'Elektrik şebekesi tesis çalışmaları yapılması planlanan cadde ve sokak isimleri aşağıdaki listede sunulmuştur.',
            'Gerekli kazı izninin verilmesi hususunda,',
            'Gereğini arz ederim.',
        ];
    }

    private static function buildCoverLetterStreets(Application $app): array
    {
        $mahalleler = [];

        if ($app->relationLoaded('gisCizimleri')) {
            foreach ($app->gisCizimleri as $cizim) {
                foreach ($cizim->yolIliskileri as $yol) {
                    $mahalleAdi = $yol->mahalle ? mb_strtoupper(trim($yol->mahalle), 'UTF-8') : 'BELİRTİLMEMİŞ MAHALLE';
                    if (!isset($mahalleler[$mahalleAdi])) {
                        $mahalleler[$mahalleAdi] = ['ad' => $mahalleAdi, 'sokaklar' => []];
                    }
                    $yolAdi = $yol->yol_adi ? mb_strtoupper(trim($yol->yol_adi), 'UTF-8') : '';
                    if ($yolAdi && !in_array($yolAdi, $mahalleler[$mahalleAdi]['sokaklar'])) {
                        $mahalleler[$mahalleAdi]['sokaklar'][] = $yolAdi;
                    }
                }
            }
        }

        if ($app->relationLoaded('gisNoktalari')) {
            foreach ($app->gisNoktalari as $nokta) {
                if ($nokta->mahalle) {
                    $mahalleAdi = mb_strtoupper(trim($nokta->mahalle), 'UTF-8');
                    if (!isset($mahalleler[$mahalleAdi])) {
                        $mahalleler[$mahalleAdi] = ['ad' => $mahalleAdi, 'sokaklar' => []];
                    }
                }
                if ($nokta->parsel) {
                    $parselAdi = 'PARSEL: ' . ($nokta->ada ? $nokta->ada . '/' : '') . $nokta->parsel;
                    $mh = $nokta->mahalle ? mb_strtoupper(trim($nokta->mahalle), 'UTF-8') : 'BELİRTİLMEMİŞ MAHALLE';
                    if (!isset($mahalleler[$mh])) {
                        $mahalleler[$mh] = ['ad' => $mh, 'sokaklar' => []];
                    }
                    if (!in_array($parselAdi, $mahalleler[$mh]['sokaklar'])) {
                        $mahalleler[$mh]['sokaklar'][] = $parselAdi;
                    }
                }
            }
        }

        if (!empty($app->address_text)) {
            $lines = explode("\n", $app->address_text);
            $mhKey = 'ADRES';
            $mahalleler[$mhKey] = ['ad' => 'ADRES BİLGİSİ', 'sokaklar' => []];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line) {
                    $mahalleler[$mhKey]['sokaklar'][] = $line;
                }
            }
        }

        return array_values($mahalleler);
    }

    private static function buildRuhsatZeminler(Application $app): array
    {
        $d = (float)($app->deposit_amount ?? 0);
        return [
            ['ad' => 'ASFALT (SICAK KARIŞIM)', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => ''],
            ['ad' => 'ASFALT (SOĞUK ASFALT)', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => 'RUHSAT HARCI', 'toplam' => number_format($d, 2, ',', '.')],
            ['ad' => 'PARKE', 'birim' => 'm2', 'miktar' => '5,00', 'tutar' => '45,00', 'diger' => 'KESIF BEDELİ', 'toplam' => '45,00'],
            ['ad' => 'BETON', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => number_format($d * 0.09, 2, ',', '.')],
            ['ad' => 'STABİLİZE', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => number_format($d * 1.21, 2, ',', '.')],
            ['ad' => 'TRETUAR (PARKE PRİZM)', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => number_format($d * 0.5, 2, ',', '.')],
            ['ad' => 'TRETUAR (KARO)', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => 'GENEL TOPLAM', 'toplam' => number_format($d * 1.8, 2, ',', '.')],
            ['ad' => 'TRETUAR (MERMER)', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => ''],
            ['ad' => 'TRETUAR (BAZALT)', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => ''],
            ['ad' => 'BORDÜR (BETON)', 'birim' => 'm', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => ''],
            ['ad' => 'BORDÜR (BAZALT)', 'birim' => 'm', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => ''],
            ['ad' => 'ÇİM', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => ''],
            ['ad' => 'TOPRAK', 'birim' => 'm2', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => ''],
            ['ad' => 'GÖRME ENGELLİ KARO', 'birim' => 'm', 'miktar' => '0,00', 'tutar' => '0,00', 'diger' => '', 'toplam' => ''],
        ];
    }

    private static function getRuhsatSartlari(): array
    {
        return [
            '1- Kazıya başlamadan once tum guvenlik onlemleri alınacaktır.',
            '2- ASFALT olan ZEMİNLERDE ASFALT KESME MAKİNASI KULLANILACAKTIR.',
            '3- Kış sezonunda TAHRİP EDİLEN TOPRAK VE STABİLİZE ZEMİNLER HARİÇ DİĞER ZEMİNLER BETONLANACAKTIR.',
            '4- GENEL KURUL KARARI GEREĞİ KAZI GENİŞLİĞİ EN AZ 0,60 (ALTMIŞ) cM DİR.',
            '5- RUHSAT TARİHİNDEN İTİBAREN 2 (İKİ) YIL İÇERİSİNDE ALINMAYAN TEMİNATLAR GELİR KAYDEDİLİR.',
            '6- EYYÜBİYE BELEDİYESİ\'NİN RUHSAT TARİHİNDEN ÖNCE VE RUHSAT SÜRESİNDEN SONRA DOĞMUŞ VE DOĞACAK ZARARLARA İLİŞKİN TAZMİNAT VS. HAKKI SAKLIDIR.',
            '7- İLAVE YAPILACAK KAZILAR İÇİN EK RUHSAT ALINACAKTIR.',
            '8- KAZI ÇALIŞMALARI ESNASINDA STABİLİZE VE TOPRAK HARİCİNDEKİ ZEMİNLERDE KAZIDAN ÇIKAN HAFRİYAT DOĞRUDAN ARAÇLARA YÜKLENEREK NAKLEDİLECEKTİR.',
            '9- AYKOME YÖNETMELİK VE EKLERİNİ OKUDUM ŞARTLARINI KABUL EDİYORUM.',
        ];
    }

    private static function getAltyapiSartlari(): array
    {
        return [
            '1- Altyapı : Yol üst kaplaması altında kalan yol kısmı ile içme suyu, Kanalizasyon, Elektrik, Doğalgaz, Telekominikasyon bağlantı hatları, Merkezi Isıtma gibi yer altından geçen tüm tesisleri kapsar.',
            '2- EYYÜBİYE BELEDİYESİ sınırları içerisinde Belediyeye ait yollarda yapılacak altyapı tesisi ile ilgili ruhsatlar ve diğer bütün işlemler ŞANLIURFA BÜYÜKŞEHİR BELEDİYESİ ALTYAPI KOORDİNASYON MERKEZİ kararları doğrultusunda AYKOME Birimince verilecek izne göre yürütülür.',
        ];
    }

    /**
     * TAHAKKUK MATBU YASA: Belgede sistemdeki TÜM Zemin Tipleri alt alta bastırılır;
     * kazılan/seçilen zeminin hizasına gerçek miktar/fiyat/tutar gelir, başvuruda
     * yer almayan zeminler "0,00" kalır. Zemin listesi dinamiktir (SurfaceType::all()),
     * asla hard-code string dizi kullanılmaz.
     */
    public static function buildMetrajSatirlari(Application $app): array
    {
        $app->loadMissing(['surfaceLines.surfaceType', 'institution']);

        $lines = $app->surfaceLines ?? collect();
        if (! $lines instanceof \Illuminate\Support\Collection) {
            $lines = collect($lines);
        }

        // Başvuru satırlarını ad bazlı (büyük-küçük duyarsız) indexle — contains/where mantığı.
        $appLinesByName = $lines->filter(fn ($sl) => $sl->surfaceType)
            ->mapWithKeys(function ($sl) {
                $key = mb_strtolower(trim((string) $sl->surfaceType->name), 'UTF-8');

                return [$key => $sl];
            });

        $rows = [];
        foreach (\App\Models\SurfaceType::query()->orderBy('id')->get() as $st) {
            $stName = trim((string) $st->name);
            $stKey = mb_strtolower($stName, 'UTF-8');
            $matched = $appLinesByName->get($stKey);

            // Birim: bordür / görme engelli karo gibi hat işlerinde "m", diğerlerinde "m²".
            // strtolower ASCII-only olduğundan İ/ı/i/I Türkçe karakter farkını bypass eder.
            $ust = strtolower($stName);
            $birim = (str_contains($ust, 'bordür') || str_contains($ust, 'bordur') || str_contains($ust, 'görme engell') || str_contains($ust, 'gorme engell') || str_contains($ust, 'olugu') || str_contains($ust, 'oluğu')) ? 'm' : 'm2';

            if ($matched) {
                $rows[] = [
                    'ad' => mb_strtoupper($stName, 'UTF-8'),
                    'birim' => $birim,
                    'miktar' => number_format((float) ($matched->quantity ?? 0), 2, ',', '.'),
                    'birim_fiyat' => number_format((float) ($matched->surfaceType->price_per_m2 ?? 0), 2, ',', '.'),
                    'tutar' => number_format((float) ($matched->amount ?? 0), 2, ',', '.'),
                ];
            } else {
                // Başvuruda bu zemin yok — sıfır satırı (model birim fiyatı gösterilir, miktar/tutar 0).
                $rows[] = [
                    'ad' => mb_strtoupper($stName, 'UTF-8'),
                    'birim' => $birim,
                    'miktar' => '0,00',
                    'birim_fiyat' => number_format((float) ($st->price_per_m2 ?? 0), 2, ',', '.'),
                    'tutar' => '0,00',
                ];
            }
        }

        // Zemin satırı hiç yoksa bile tipik boş satır üretilir — hesap yok, 0 gösterilir.
        if (empty($rows)) {
            $rows[] = ['ad' => '—', 'birim' => 'm2', 'miktar' => '0,00', 'birim_fiyat' => '0,00', 'tutar' => '0,00'];
        }

        return $rows;
    }

    public static function buildMetrajRows(Application $app): array
    {
        $app->loadMissing(['institution', 'creator', 'surfaceLines.surfaceType', 'gisCizimleri.yolIliskileri', 'gisNoktalari']);

        $rows = [];
        $sira = 0;
        $ilce = $app->district ?? 'EYYÜBİYE';
        $projeKodu = $app->project_code ?? '';
        $isAdi = $app->work_type ?? '';
        $combinedParts = [];
        if ($projeKodu !== '') {
            $combinedParts[] = $projeKodu;
        }
        if ($isAdi !== '') {
            $combinedParts[] = $isAdi;
        }
        $projeKodu = implode(' / ', $combinedParts);
        $tarih = $app->start_date?->format('d.m.Y') ?? '';

        $mahalleList = [];
        $caddeList = [];

        if ($app->relationLoaded('gisNoktalari')) {
            foreach ($app->gisNoktalari as $n) {
                if ($n->mahalle) $mahalleList[] = mb_strtoupper(trim($n->mahalle), 'UTF-8');
            }
        }
        if ($app->relationLoaded('gisCizimleri')) {
            foreach ($app->gisCizimleri as $c) {
                foreach ($c->yolIliskileri as $y) {
                    if ($y->mahalle) $mahalleList[] = mb_strtoupper(trim($y->mahalle), 'UTF-8');
                    if ($y->yol_adi) $caddeList[] = mb_strtoupper(trim($y->yol_adi), 'UTF-8');
                }
            }
        }
        $mahalle = $mahalleList ? implode(', ', array_unique($mahalleList)) : ($app->address_text ? mb_strtoupper(trim(explode("\n", $app->address_text)[0]), 'UTF-8') : '');
        $cadde = $caddeList ? implode(', ', array_unique($caddeList)) : '';

        if ($app->relationLoaded('surfaceLines') && $app->surfaceLines->count() > 0) {
            foreach ($app->surfaceLines as $sl) {
                if (!$sl->surfaceType) continue;
                $sira++;
                $genislik = $sl->width_m ? number_format((float)$sl->width_m, 2, ',', '.') : '0,00';
                $uzunluk = $sl->length_m ? number_format((float)$sl->length_m, 2, ',', '.') : '0,00';
                $m2 = $sl->quantity ? number_format((float)$sl->quantity, 2, ',', '.') : '0,00';
                $zemin = mb_strtoupper($sl->surfaceType->name, 'UTF-8');

                $rows[] = [
                    'sira' => $sira,
                    'ilce' => $ilce,
                    'mahalle' => $mahalle,
                    'cadde' => $cadde,
                    'tarih' => $tarih,
                    'genislik' => $genislik,
                    'uzunluk' => $uzunluk,
                    'm2' => $m2,
                    'zemin' => $zemin,
                    'proje_kodu' => $projeKodu,
                    // MODÜLLER ARASI SENKRON: metraj satırı hangi application_surface_areas
                    // satırından geldiyse kimliği taşınır; düzenlenen M² editör kaydında
                    // sync_zemin_lines ile DB'ye geri beslenip Tahakkuk/Ruhsat'ı tazeler.
                    'surface_line_id' => $sl->id,
                ];
            }
        }

        if (empty($rows)) {
            $rows[] = [
                'sira' => 1,
                'ilce' => $ilce,
                'mahalle' => $mahalle,
                'cadde' => $cadde,
                'tarih' => $tarih,
                'genislik' => '0,00',
                'uzunluk' => '0,00',
                'm2' => '0,00',
                'zemin' => 'BELİRTİLMEMİŞ',
                'proje_kodu' => $projeKodu,
            ];
        }

        return $rows;
    }

    /**
     * KAZI METRAJ TAHMİNİ (PRO) — POST /admin/applications/metraj-tahmin
     * -------------------------------------------------------------
     * Başvuru formundaki "🎯 Metraj Tahmini Al" butonundan çağrılır.
     * ProjectForecastService geçmiş başvuru istatistiğinden (kurum+mahalle
     * adaptif; veri azsa global/varsayılan) tam kapsamlı tahmin üretir:
     * toplam m² + zemin tipi bazlı dağılım + AykomeMath ile fiyat öngörüsü.
     * Sıfır maliyet, offline, LLM yok.
     */
    public function metrajTahmin(Request $request, \App\Services\ProjectForecastService $forecast): JsonResponse
    {
        $validated = $request->validate([
            'institution_id' => ['nullable', 'integer', 'exists:institutions,id'],
            'mahalle' => ['nullable', 'string', 'max:255'],
            'total_area_m2' => ['required', 'numeric', 'min:0'],
            'exclude_application_id' => ['nullable', 'integer'],
        ]);

        $result = $forecast->predict(
            ! empty($validated['institution_id']) ? (int) $validated['institution_id'] : null,
            ! empty($validated['mahalle']) ? (string) $validated['mahalle'] : null,
            (float) $validated['total_area_m2'],
            ! empty($validated['exclude_application_id']) ? (int) $validated['exclude_application_id'] : null,
        );

        AuditLogger::log(
            'metraj.forecast',
            "Metraj tahmini alındı: {$result['total_m2']} m², seviye={$result['level']}, örnek={$result['sample_count']}",
            null,
            null,
            ['level' => $result['level'], 'sample_count' => $result['sample_count'], 'total_m2' => $result['total_m2']]
        );

        return response()->json($result);
    }

    public function geocodeProxy(Request $request): JsonResponse
    {
        $query = $request->query('q');
        if (! $query) {
            return response()->json(['success' => false]);
        }

        // AUTCOMPLETE LİST MODU — Nominatim çoklu sonuç (adres öneri dropdown'ı)
        if ($request->query('list')) {
            try {
                $listResponse = Http::withHeaders([
                    'User-Agent' => 'Aykome-Eyyubiye-GIS-Backend/1.0', // Zorunludur OSM kızmaz.
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'format' => 'json', 'q' => $query, 'countrycodes' => 'tr', 'limit' => $request->query('limit', 6), 'addressdetails' => 0,
                ]);

                if ($listResponse->successful()) {
                    return response()->json(['success' => true, 'list' => $listResponse->json()]);
                }
            } catch (\Exception $e) {
                // Nominatim istek hatası — sessizce geç
            }

            return response()->json(['success' => false]);
        }

        // 1. AŞAMA: NOMINATIM (OSM) — Türkiye adreslerinde en isabetli kaynak
        try {
            $osmResponse = Http::withHeaders([
                'User-Agent' => 'Aykome-Eyyubiye-GIS-Backend/1.0', // Zorunludur OSM kızmaz.
            ])->get('https://nominatim.openstreetmap.org/search', [
                'format' => 'json', 'q' => $query, 'countrycodes' => 'tr', 'limit' => 1,
            ]);

            if ($osmResponse->successful() && count($osmResponse->json()) > 0) {
                $item = $osmResponse->json()[0];

                return response()->json(['success' => true, 'lat' => $item['lat'], 'lon' => $item['lon']]);
            }
        } catch (\Exception $e) {
            // Nominatim istek hatası — Yandex fallback'ine geç
        }

        // 2. AŞAMA: PHOTON (komoot) — API key gerektirmez, Nominatim rate limit'e takılırsa devreye girer
        try {
            $photonResponse = Http::withHeaders([
                'User-Agent' => 'Aykome-Eyyubiye-GIS-Backend/1.0',
            ])->get('https://photon.komoot.io/api/', [
                'q' => $query, 'limit' => 1,
            ]);

            if ($photonResponse->successful() && count($photonResponse->json('features', [])) > 0) {
                $coords = $photonResponse->json('features.0.geometry.coordinates'); // [lng, lat]

                return response()->json(['success' => true, 'lat' => $coords[1], 'lon' => $coords[0]]);
            }
        } catch (\Exception $e) {
            // Photon istek hatası — Yandex fallback'ine geç
        }

        // 3. AŞAMA: YANDEX — Photon da boş dönerse üçüncü deneme
        $apiKey = config('services.yandex.api_key');
        if ($apiKey) {
            try {
                $url = "https://geocode-maps.yandex.ru/1.x/?apikey={$apiKey}&format=json&geocode=".urlencode($query).'&results=1';
                $yandexResponse = Http::get($url);
                if ($yandexResponse->successful()) {
                    $pos = $yandexResponse->json('response.GeoObjectCollection.featureMember.0.GeoObject.Point.pos');
                    if ($pos) {
                        $coords = explode(' ', $pos);

                        return response()->json(['success' => true, 'lat' => $coords[1], 'lon' => $coords[0]]);
                    }
                }
            } catch (\Exception $e) {
                // Yandex istek hatası — sessizce geç
            }
        }

        return response()->json(['success' => false]);
    }

    /** Başvuruya ait kurum logosunu data URI (base64) olarak döndürür; yoksa null. */
    private function institutionLogoBase64(Application $application): ?string
    {
        if (! $application->institution || ! $application->institution->logo_path) {
            return null;
        }
        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');
            $fileContent = $disk->get($application->institution->logo_path);
            if (! $fileContent) {
                return null;
            }
            $mime = $disk->mimeType($application->institution->logo_path) ?: 'image/png';

            return 'data:' . $mime . ';base64,' . base64_encode($fileContent);
        } catch (\Exception $e) {
            return null;
        }
    }
}
