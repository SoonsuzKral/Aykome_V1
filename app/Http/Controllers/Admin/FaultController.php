<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaultController extends Controller
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

        $query = Application::query()
            ->with(['institution', 'creator'])
            ->where('application_type', 'ariza')
            ->latest();

        if (! $user->isMunicipalityPersonel()) {
            $query->where('institution_id', $user->institution_id);
        }

        if ($filters['q'] !== '') {
            $needle = $filters['q'];
            $query->where(function ($q) use ($needle): void {
                $q->where('application_no', 'like', "%{$needle}%")
                    ->orWhere('applicant_first_name', 'like', "%{$needle}%")
                    ->orWhere('applicant_last_name', 'like', "%{$needle}%")
                    ->orWhere('address_text', 'like', "%{$needle}%");
            });
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if ($filters['institution_id'] !== '') {
            $query->where('institution_id', $filters['institution_id']);
        }

        // Sadece belediye personeli tüm kurumları görebilsin
        $institutions = $user->isMunicipalityPersonel()
            ? \App\Models\Institution::orderBy('name')->get()
            : collect();

        return view('admin.faults.index', [
            'applications' => $query->paginate(15)->withQueryString(),
            'filters' => $filters,
            'institutions' => $institutions,
        ]);
    }

    /**
     * Toplu Arıza → Tahakkuk / Yer Formu iskelet rotası.
     * Seçili arıza kayıtlarını toplu tahakkuka dönüştürme motoru buradan başlayacak.
     */
    public function bulkTahakkuk(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Application::class);

        $ids = $request->input('ids', []);
        $ids = array_filter(array_map('intval', (array) $ids));

        if (empty($ids)) {
            return back()->with('error', 'Toplu işlem için en az bir arıza kaydı seçmelisiniz.');
        }

        AuditLogger::log(
            'fault.bulk_tahakkuk',
            'Toplu tahakkuk isteği: ' . count($ids) . ' arıza kaydı seçildi',
            'Application',
            null,
            ['ids' => $ids]
        );

        return back()->with('success', count($ids) . ' arıza kaydı toplu tahakkuka dönüştürme motoruna gönderildi (iskelet hazır).');
    }
}
