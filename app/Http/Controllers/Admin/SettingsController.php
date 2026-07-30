<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PermitSetting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function permit(): View
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $settings = PermitSetting::getSingleton();

        return view('admin.settings.permit', compact('settings'));
    }

    public function updatePermit(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        $request->validate([
            'institution_name'         => ['nullable', 'string', 'max:255'],
            'institution_address'      => ['nullable', 'string', 'max:512'],
            'department_name'          => ['nullable', 'string', 'max:255'],
            'director_name'            => ['nullable', 'string', 'max:191'],
            'director_title'           => ['nullable', 'string', 'max:191'],
            'preparer_name'            => ['nullable', 'string', 'max:191'],
            'preparer_title'           => ['nullable', 'string', 'max:191'],
            'approver_name'            => ['nullable', 'string', 'max:191'],
            'approver_title'           => ['nullable', 'string', 'max:191'],
            'secondary_approver_name'  => ['nullable', 'string', 'max:191'],
            'secondary_approver_title' => ['nullable', 'string', 'max:191'],
            'validity_agreement'       => ['nullable', 'string'],
            'footer_note'              => ['nullable', 'string', 'max:512'],
            'institution_logo'         => ['nullable', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
            'director_signature'       => ['nullable', 'image', 'mimes:png,jpg,jpeg',     'max:2048'],
            'preparer_signature'       => ['nullable', 'image', 'mimes:png,jpg,jpeg',     'max:2048'],
            'municipality_stamp'       => ['nullable', 'image', 'mimes:png,jpg,jpeg',     'max:2048'],
        ]);

        $settings = PermitSetting::getSingleton();

        $data = $request->only([
            'institution_name',
            'institution_address',
            'department_name',
            'director_name',
            'director_title',
            'preparer_name',
            'preparer_title',
            'approver_name',
            'approver_title',
            'secondary_approver_name',
            'secondary_approver_title',
            'validity_agreement',
            'footer_note',
        ]);

        // ── Logo upload ───────────────────────────────────────────────────────
        if ($request->hasFile('institution_logo')) {
            if ($settings->institution_logo_path) {
                Storage::disk('public')->delete($settings->institution_logo_path);
            }
            $data['institution_logo_path'] = $request->file('institution_logo')
                ->store('permit/logos', 'public');
        }

        // ── Signature upload ──────────────────────────────────────────────────
        if ($request->hasFile('director_signature')) {
            if ($settings->director_signature_path) {
                Storage::disk('public')->delete($settings->director_signature_path);
            }
            $data['director_signature_path'] = $request->file('director_signature')
                ->store('permit/signatures', 'public');
        }

        // ── Preparer signature upload ─────────────────────────────────────────
        if ($request->hasFile('preparer_signature')) {
            if ($settings->preparer_signature_path) {
                Storage::disk('public')->delete($settings->preparer_signature_path);
            }
            $data['preparer_signature_path'] = $request->file('preparer_signature')
                ->store('permit/signatures', 'public');
        }

        // ── Stamp upload ──────────────────────────────────────────────────────
        if ($request->hasFile('municipality_stamp')) {
            if ($settings->municipality_stamp_path) {
                Storage::disk('public')->delete($settings->municipality_stamp_path);
            }
            $data['municipality_stamp_path'] = $request->file('municipality_stamp')
                ->store('permit/stamps', 'public');
        }

        // ── Delete actions ────────────────────────────────────────────────────
        if ($request->boolean('delete_logo') && $settings->institution_logo_path) {
            Storage::disk('public')->delete($settings->institution_logo_path);
            $data['institution_logo_path'] = null;
        }
        if ($request->boolean('delete_signature') && $settings->director_signature_path) {
            Storage::disk('public')->delete($settings->director_signature_path);
            $data['director_signature_path'] = null;
        }
        if ($request->boolean('delete_preparer_signature') && $settings->preparer_signature_path) {
            Storage::disk('public')->delete($settings->preparer_signature_path);
            $data['preparer_signature_path'] = null;
        }
        if ($request->boolean('delete_stamp') && $settings->municipality_stamp_path) {
            Storage::disk('public')->delete($settings->municipality_stamp_path);
            $data['municipality_stamp_path'] = null;
        }

        $settings->update($data);

        AuditLogger::log(
            'settings.permit_updated',
            'Ruhsat belgesi ayarları güncellendi.',
            'PermitSetting',
            $settings->id,
        );

        return back()->with('success', 'Ruhsat belgesi ayarları kaydedildi.');
    }
}
