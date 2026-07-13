<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreExcavationPermitSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PreExcavationPermitSettingController extends Controller
{
    public function edit(): View
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $settings = PreExcavationPermitSetting::getSingleton();

        return view('admin.settings.pre_excavation_permit', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'header_text' => ['nullable', 'string'],
            'footer_text' => ['nullable', 'string'],
            'sections' => ['nullable', 'string'],
            'approver_name' => ['nullable', 'string', 'max:191'],
            'approver_title' => ['nullable', 'string', 'max:191'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'signature' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
            'stamp' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048'],
        ]);

        $settings = PreExcavationPermitSetting::getSingleton();
        $data = $request->only(['title', 'header_text', 'footer_text', 'approver_name', 'approver_title']);

        // Decode sections JSON
        if ($request->has('sections')) {
            $decoded = json_decode($request->input('sections'), true);
            $data['sections'] = is_array($decoded) ? $decoded : [];
        }

        // File upload: logo
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('pre-excavation-permit', 'public');
        }
        if ($request->has('delete_logo')) {
            if ($settings->logo_path) Storage::disk('public')->delete($settings->logo_path);
            $data['logo_path'] = null;
        }

        // File upload: signature
        if ($request->hasFile('signature')) {
            $data['signature_path'] = $request->file('signature')->store('pre-excavation-permit', 'public');
        }
        if ($request->has('delete_signature')) {
            if ($settings->signature_path) Storage::disk('public')->delete($settings->signature_path);
            $data['signature_path'] = null;
        }

        // File upload: stamp
        if ($request->hasFile('stamp')) {
            $data['stamp_path'] = $request->file('stamp')->store('pre-excavation-permit', 'public');
        }
        if ($request->has('delete_stamp')) {
            if ($settings->stamp_path) Storage::disk('public')->delete($settings->stamp_path);
            $data['stamp_path'] = null;
        }

        $settings->update($data);

        AuditLogger::log('settings.pre_excavation_permit_updated', 'Ön kazı izin belgesi ayarları güncellendi.', 'PreExcavationPermitSetting', $settings->id);

        return back()->with('success', 'Ön kazı izin belgesi ayarları kaydedildi.');
    }
}
