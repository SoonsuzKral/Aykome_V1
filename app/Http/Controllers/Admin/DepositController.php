<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Application::class);

        $user = $request->user();
        $tab = $request->query('tab', 'pending') === 'refunded' ? 'refunded' : 'pending';

        $query = Application::query()
            ->with(['institution', 'creator'])
            ->where('deposit_amount', '>', 0)
            ->where('application_type', 'basvuru')
            ->latest();

        if (! $user->isMunicipalityPersonel()) {
            $query->where('institution_id', $user->institution_id);
        }

        if ($tab === 'refunded') {
            $query->whereIn('deposit_status', ['refunded', 'irat']);
        } else {
            $query->where('deposit_status', 'pending');
        }

        return view('admin.deposits.index', [
            'applications' => $query->paginate(15)->withQueryString(),
            'tab' => $tab,
        ]);
    }

    public function refund(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'refund_document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ]);

        $file = $validated['refund_document'];
        $safeAppNo = $application->application_no ?? (string) $application->id;
        $storedPath = Storage::disk('public')->putFileAs(
            'deposit-refunds',
            $file,
            sprintf('teminat-iade-%s-%s.%s', $safeAppNo, now()->format('YmdHis'), $file->getClientOriginalExtension())
        );

        if (! $storedPath) {
            return back()->with('error', 'Dekont dosyası kaydedilemedi.');
        }

        $application->update([
            'deposit_refunded_at' => now(),
            'deposit_refund_doc' => $storedPath,
            'is_deposit_refunded' => true,
            'deposit_status' => 'refunded',
            'status' => ApplicationStatus::Approved,
        ]);

        AuditLogger::log(
            'deposit.refunded',
            "Teminat iadesi yapıldı: {$application->application_no} ({$application->deposit_amount} TL)",
            'Application',
            $application->id,
            ['document' => $storedPath]
        );

        return back()->with('success', "Teminat iadesi tamamlandı: {$application->application_no}");
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'deposit_status' => ['required', 'string', 'in:pending,refunded,irat'],
            'refund_notes' => ['nullable', 'string', 'max:2000'],
            'refund_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:20480'],
        ]);

        $depositStatus = $validated['deposit_status'];
        $data = [
            'deposit_status' => $depositStatus,
            'deposit_refund_notes' => $validated['refund_notes'] ?? null,
        ];

        if (! empty($validated['refund_document'])) {
            $file = $validated['refund_document'];
            $safeAppNo = $application->application_no ?? (string) $application->id;
            $storedPath = Storage::disk('public')->putFileAs(
                'deposit-refunds',
                $file,
                sprintf('teminat-%s-%s-%s.%s', $depositStatus, $safeAppNo, now()->format('YmdHis'), $file->getClientOriginalExtension())
            );

            if ($storedPath) {
                $data['deposit_refund_doc'] = $storedPath;
            }
        }

        if ($depositStatus === 'refunded') {
            $data['deposit_refunded_at'] = now();
            $data['is_deposit_refunded'] = true;
            $data['status'] = ApplicationStatus::Approved;
        } elseif ($depositStatus === 'irat') {
            $data['is_deposit_refunded'] = true;
        } else {
            $data['is_deposit_refunded'] = false;
            $data['deposit_refunded_at'] = null;
        }

        $application->update($data);

        AuditLogger::log(
            "deposit.status_{$depositStatus}",
            "Teminat işlemi güncellendi: {$application->application_no} → {$depositStatus}",
            'Application',
            $application->id,
            ['notes' => $validated['refund_notes'] ?? null]
        );

        $label = match ($depositStatus) {
            'refunded' => 'İade Edildi / Ödendi',
            'irat' => 'Kuruma İrat Kaydedildi (Süre Aşımı)',
            default => 'Bekliyor',
        };

        return back()->with('success', "Teminat işlemi güncellendi ({$label}): {$application->application_no}");
    }
}
