<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMapDrawingRequest;
use App\Models\Application;
use App\Models\Institution;
use App\Services\MapDrawingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapMonitorController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Application::class);

        $user    = $request->user();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
            'institution_id' => trim((string) $request->query('institution_id', '')),
            'drawing' => trim((string) $request->query('drawing', 'all')),
        ];

        $applicationsQuery = Application::query()
            ->with([
                'institution:id,name,slug,color_code,is_municipality',
                'excavationAreas:id,application_id,polygon_geojson,total_area_m2,center_lat,center_lng,address_text,updated_at',
            ])
            ->latest();

        // ── Data isolation ────────────────────────────────────────────────
        if ($user->hasRole('field-team')) {
            // Saha personeli: sadece kendisine atanmış görevlerdeki başvurular
            $applicationsQuery->whereHas('fieldTasks', fn ($q) => $q->where('assigned_to', $user->id));
        } elseif ($user->hasRole(['institution-staff', 'institution-manager'])) {
            // Kurum çalışanı: sadece kendi kurumunun başvuruları
            $applicationsQuery->where('institution_id', $user->institution_id);
        }

        $statusValues = collect(ApplicationStatus::cases())->map(fn (ApplicationStatus $status) => $status->value);
        if ($filters['status'] !== '' && $statusValues->contains($filters['status'])) {
            $applicationsQuery->where('status', $filters['status']);
        } else {
            $filters['status'] = '';
        }


        if ($filters['q'] !== '') {
            $needle = $filters['q'];
            $applicationsQuery->where(function ($q) use ($needle): void {
                $q->where('application_no', 'like', "%{$needle}%")
                    ->orWhere('address_text', 'like', "%{$needle}%");
            });
        }


        if ($filters['institution_id'] !== '') {
            $institutionId = (int) $filters['institution_id'];
            if ($institutionId > 0) {
                $applicationsQuery->where('institution_id', $institutionId);
            }
        }
        if (! in_array($filters['drawing'], ['all', 'polygon', 'marker', 'none'], true)) {
            $filters['drawing'] = 'all';
        }

        if ($filters['drawing'] === 'polygon') {
            $applicationsQuery->whereHas('excavationAreas', fn ($q) => $q
                ->whereNotNull('polygon_geojson')
                ->where('polygon_geojson', '!=', ''));
        }

        if ($filters['drawing'] === 'none') {
            $applicationsQuery->whereDoesntHave('excavationAreas', fn ($q) => $q
                ->whereNotNull('polygon_geojson')
                ->where('polygon_geojson', '!=', '')
                ->orWhere(function ($scope) {
                    $scope->whereNotNull('center_lat')->whereNotNull('center_lng');
                }));
        }

        $applications = $applicationsQuery
            ->limit(200)
            ->get(['id', 'application_no', 'institution_id', 'status', 'address_text', 'total_area_m2']);

        $mapApplications = $applications
            ->map(function (Application $application): array {
                $status = $application->status instanceof \BackedEnum
                    ? $application->status->value
                    : (string) $application->status;

                $area = $application->excavationAreas
                    ->sortByDesc('updated_at')
                    ->first();

                $institution = $application->institution;
                $color = $this->resolveInstitutionColor(
                    $institution?->slug,
                    $institution?->name,
                    $institution?->color_code,
                    (bool) $institution?->is_municipality,
                );

                $statusBadge = $this->statusBadgeMeta($status);

                return [
                    'id' => $application->id,
                    'application_no' => $application->application_no,
                    'status' => $status,
                    'status_label' => $statusBadge['label'],
                    'status_badge_class' => $statusBadge['class'],
                    'address_text' => $application->address_text,
                    'total_area_m2' => (float) $application->total_area_m2,
                    'detail_url' => route('admin.applications.show', $application),
                    'institution' => [
                        'id' => $institution?->id,
                        'name' => $institution?->name,
                        'slug' => $institution?->slug,
                        'color_code' => $institution?->color_code,
                        'draw_color' => $color,
                        'is_municipality' => (bool) $institution?->is_municipality,
                    ],
                    'drawing' => $area ? [
                        'polygon_geojson' => $area->polygon_geojson,
                        'total_area_m2' => (float) $area->total_area_m2,
                        'center_lat' => $area->center_lat !== null ? (float) $area->center_lat : null,
                        'center_lng' => $area->center_lng !== null ? (float) $area->center_lng : null,
                        'address_text' => $area->address_text,
                    ] : null,
                ];
            })
            ->values();

        $defaultCenter = $mapApplications
            ->first(fn (array $row) => ($row['drawing']['center_lat'] ?? null) !== null && ($row['drawing']['center_lng'] ?? null) !== null);

        return view('admin.map.index', [
            'mapApplications' => $mapApplications,
            'googleMapsApiKey' => config('services.google_maps.api_key') ?: config('aykome.google_maps_api_key'),
            'defaultCenter' => $defaultCenter
                ? [
                    'lat' => $defaultCenter['drawing']['center_lat'],
                    'lng' => $defaultCenter['drawing']['center_lng'],
                ]
                : ['lat' => 39.93, 'lng' => 32.85],
            'filters' => $filters,
            'statuses' => ApplicationStatus::cases(),
            'institutions' => Institution::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function storeDrawing(
        StoreMapDrawingRequest $request,
        Application $application,
        MapDrawingService $mapDrawingService,
    ): RedirectResponse {
        $this->authorize('update', $application);

        $data = $request->validated();
        $polygonGeoJson = $data['polygon_geojson'] ?? null;
        $totalAreaM2 = isset($data['total_area_m2']) ? (float) $data['total_area_m2'] : null;

        if (($totalAreaM2 === null || $totalAreaM2 <= 0) && is_string($polygonGeoJson) && $polygonGeoJson !== '') {
            $totalAreaM2 = $mapDrawingService->calculateAreaM2FromGeoJson($polygonGeoJson);
        }

        $addressText = array_key_exists('address_text', $data)
            ? (($data['address_text'] ?? '') !== '' ? $data['address_text'] : null)
            : $application->address_text;

        $mapDrawingService->syncPrimaryArea($application, [
            'polygon_geojson' => $polygonGeoJson,
            'total_area_m2' => $totalAreaM2 ?? 0,
            'center_lat' => $data['center_lat'] ?? null,
            'center_lng' => $data['center_lng'] ?? null,
            'address_text' => $addressText,
        ]);

        $application->update([
            'total_area_m2' => $totalAreaM2 ?? 0,
            'address_text' => $addressText,
        ]);

        return back()->with('success', 'Harita çizimi kaydedildi.');
    }

    private function resolveInstitutionColor(?string $slug, ?string $name, ?string $colorCode, bool $isMunicipality): string
    {
        $normalizedSlug = strtolower((string) $slug);
        $normalizedName = mb_strtolower((string) $name, 'UTF-8');

        if ($isMunicipality || $normalizedSlug === 'belediye' || str_contains($normalizedName, 'belediye')) {
            return '#16A34A';
        }

        if ($normalizedSlug === 'tedas' || str_contains($normalizedName, 'tedaş') || str_contains($normalizedName, 'tedas')) {
            return '#DC2626';
        }

        if ($normalizedSlug === 'suski' || str_contains($normalizedName, 'şuski') || str_contains($normalizedName, 'suski')) {
            return '#2563EB';
        }

        if ($normalizedSlug === 'aksa' || str_contains($normalizedName, 'aksa')) {
            return '#EA580C';
        }

        return $colorCode ?: '#6B7280';
    }

    private function statusBadgeMeta(ApplicationStatus|string|null $status): array
    {
        $value = $status instanceof ApplicationStatus ? $status->value : (string) $status;

        return match ($value) {
            ApplicationStatus::Draft->value => ['label' => 'Taslak', 'class' => 'bg-slate-100 text-slate-700'],
            ApplicationStatus::Submitted->value => ['label' => 'Gönderildi', 'class' => 'bg-sky-100 text-sky-700'],
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
