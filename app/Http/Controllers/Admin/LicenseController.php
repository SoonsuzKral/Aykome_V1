<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LicenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', License::class);

        return view('admin.licenses.index', [
            'licenses' => License::query()->with('institution')->orderByDesc('id')->paginate(12),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', License::class);

        return view('admin.licenses.create', [
            'institutions' => Institution::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', License::class);

        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:120', 'unique:licenses,license_key'],
            'owner_name' => ['required', 'string', 'max:255'],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['required', 'date'],
            'is_active' => ['boolean'],
            'user_limit' => ['nullable', 'integer', 'min:0'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        License::query()->create($data);

        return redirect()->route('admin.licenses.index')->with('success', 'Lisans kaydedildi.');
    }

    public function edit(License $license): View
    {
        $this->authorize('update', $license);

        return view('admin.licenses.edit', [
            'license' => $license,
            'institutions' => Institution::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, License $license): RedirectResponse
    {
        $this->authorize('update', $license);

        $data = $request->validate([
            'license_key' => ['required', 'string', 'max:120', 'unique:licenses,license_key,'.$license->id],
            'owner_name' => ['required', 'string', 'max:255'],
            'institution_id' => ['nullable', 'exists:institutions,id'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['required', 'date'],
            'is_active' => ['boolean'],
            'user_limit' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $license->update($data);

        return redirect()->route('admin.licenses.index')->with('success', 'Lisans güncellendi.');
    }

    /**
     * Manuel kilit: lisansı anında pasif yap.
     */
    public function kill(License $license): RedirectResponse
    {
        $this->authorize('update', $license);

        $license->update(['is_active' => false]);

        return redirect()->route('admin.licenses.index')
            ->with('success', "🔴 {$license->owner_name} lisansı durduruldu.");
    }

    /**
     * Hızlı yenileme: valid_until +1 yıl ve is_active = true.
     * Süresi dolmuşsa bugünden itibaren 1 yıl; aktifse mevcut bitiş tarihinden 1 yıl.
     */
    public function renew(License $license): RedirectResponse
    {
        $this->authorize('update', $license);

        $base = ($license->valid_until && $license->valid_until->isFuture())
            ? $license->valid_until
            : now();

        $newUntil = $base->copy()->addYear();

        $license->update([
            'valid_until' => $newUntil,
            'is_active'   => true,
        ]);

        return redirect()->route('admin.licenses.index')
            ->with('success', "✅ Lisans 1 yıl uzatıldı. Yeni bitiş: {$newUntil->format('d.m.Y')} — Kilitler açıldı.");
    }
}
