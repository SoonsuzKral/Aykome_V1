<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ExtraPermit;
use App\Models\SurfaceType;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExtraPermitController extends Controller
{
    /**
     * Ek ruhsatlar yalnızca belediye (merkez) personeli içindir.
     * Alt kurum personeli bu modüle erişemez.
     */
    private function guardMunicipalityOnly(): void
    {
        abort_unless(request()->user()->isMunicipalityPersonel(), 403);
    }

    public function index(Application $application): View
    {
        $this->guardMunicipalityOnly();
        $this->authorize('view', $application);
        $application->load('extraPermits');
        return view('admin.extra-permits.index', [
            'application' => $application,
            'extraPermits' => $application->extraPermits,
        ]);
    }

    public function create(Application $application): View
    {
        $this->guardMunicipalityOnly();
        $this->authorize('view', $application);
        $surfaceTypes = SurfaceType::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'price_per_m2']);
        return view('admin.extra-permits.create', [
            'application' => $application,
            'surfaceTypes' => $surfaceTypes,
        ]);
    }

    public function store(Request $request, Application $application): RedirectResponse
    {
        $this->guardMunicipalityOnly();
        $this->authorize('view', $application);

        $validated = $request->validate([
            'ek_metraj_m' => 'required|numeric|min:0.01|max:99999',
            'surface_lines' => 'nullable|array',
            'surface_lines.*.surface_type_id' => 'required|exists:surface_types,id',
            'surface_lines.*.width_m' => 'required|numeric|min:0.01',
            'surface_lines.*.length_m' => 'required|numeric|min:0.01',
            'surface_lines.*.quantity' => 'required|numeric|min:0',
            'surface_lines.*.amount' => 'required|numeric|min:0',
            'surface_lines.*.address' => 'nullable|string|max:500',
        ]);

        $extraPermit = $application->extraPermits()->create([
            'ek_metraj_m' => $validated['ek_metraj_m'],
            'surface_lines' => $validated['surface_lines'] ?? [],
            'total_price' => collect($validated['surface_lines'] ?? [])->sum('amount'),
            'status' => 'pending',
        ]);

        AuditLogger::log('extra_permit.create', "Ek ruhsat oluşturuldu: {$extraPermit->id} ({$validated['ek_metraj_m']}m)", 'ExtraPermit', $extraPermit->id);

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Ek ruhsat başarıyla oluşturuldu.');
    }

    public function show(Application $application, ExtraPermit $extraPermit): View
    {
        $this->guardMunicipalityOnly();
        $this->authorize('view', $application);
        $extraPermit->load('application');
        return view('admin.extra-permits.show', [
            'application' => $application,
            'extraPermit' => $extraPermit,
        ]);
    }

    public function destroy(Application $application, ExtraPermit $extraPermit): RedirectResponse
    {
        $this->guardMunicipalityOnly();
        $this->authorize('view', $application);
        $extraPermit->delete();

        AuditLogger::log('extra_permit.delete', "Ek ruhsat silindi: {$extraPermit->id}", 'ExtraPermit', $extraPermit->id);

        return redirect()->route('admin.applications.show', $application)
            ->with('success', 'Ek ruhsat silindi.');
    }
}
