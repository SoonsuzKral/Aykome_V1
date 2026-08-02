<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentSignatorySetting;
use App\Models\Institution;
use App\Services\SignatoryEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (! $request->user()?->hasAnyRole(['super-admin', 'municipality-admin'])) {
                abort(403, 'Bu sayfaya erişim yetkiniz yok.');
            }
            return $next($request);
        });
    }

    public function index(Request $request, ?Institution $institution = null): View
    {
        $institutions = Institution::query()
            ->orderBy('is_municipality', 'desc')
            ->orderBy('name')
            ->get(['id', 'name', 'is_municipality']);

        $selectedId = $institution?->id ?? (int) $request->query('institution_id', 0);
        $scope = $selectedId ? $institutions->firstWhere('id', $selectedId) : null;

        $settings = DocumentSignatorySetting::query()
            ->when($scope, fn ($q) => $q->where('institution_id', $scope->id), fn ($q) => $q->whereNull('institution_id'))
            ->orderBy('document_type')
            ->orderBy('sort')
            ->get();

        return view('admin.document-settings.index', [
            'institutions' => $institutions,
            'scope' => $scope,
            'settings' => $settings,
            'documentTypes' => SignatoryEngine::documentTypes(),
            'roleKeys' => SignatoryEngine::roleKeys(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'institution_id' => 'nullable|exists:institutions,id',
            'document_type' => 'required|string|max:50',
            'role_key' => 'nullable|string|max:50',
            'unvan' => 'nullable|string|max:255',
            'ad_soyad' => 'nullable|string|max:255',
            'sort' => 'nullable|integer|min:0',
        ]);

        DocumentSignatorySetting::create([
            'institution_id' => $data['institution_id'] ?: null,
            'document_type' => $data['document_type'],
            'role_key' => $data['role_key'] ?: null,
            'unvan' => $data['unvan'] ?: null,
            'ad_soyad' => $data['ad_soyad'] ?: null,
            'sort' => (int) ($data['sort'] ?? 0),
            'is_active' => true,
        ]);

        return back()->with('success', 'İmzacı ayarı eklendi.');
    }

    public function update(Request $request, DocumentSignatorySetting $setting): RedirectResponse
    {
        $data = $request->validate([
            'unvan' => 'nullable|string|max:255',
            'ad_soyad' => 'nullable|string|max:255',
            'sort' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $setting->update([
            'unvan' => $data['unvan'] ?: null,
            'ad_soyad' => $data['ad_soyad'] ?: null,
            'sort' => (int) ($data['sort'] ?? $setting->sort),
            'is_active' => (bool) ($request->boolean('is_active')),
        ]);

        return back()->with('success', 'İmzacı ayarı güncellendi.');
    }

    public function destroy(DocumentSignatorySetting $setting): RedirectResponse
    {
        $setting->delete();

        return back()->with('success', 'İmzacı ayarı silindi.');
    }
}
