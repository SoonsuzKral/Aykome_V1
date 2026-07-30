<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Institution;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct()
    {
        // Gelişmiş rapor motoru, DataTables AJAX ve dışa aktarma yalnızca pro.advanced_reports yetkisiyle erişilebilir.
        $this->middleware('can:pro.advanced_reports')->only(['advanced', 'data', 'exportPdf', 'exportCsv']);
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Application::class);

        $byInstitution = Institution::query()
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->get();

        $byStatus = Application::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return view('admin.reports.index', compact('byInstitution', 'byStatus'));
    }

    public function advanced(Request $request): View
    {
        $this->authorize('viewAny', Application::class);

        $institutions = Institution::query()->orderBy('name')->get();
        $statuses     = ApplicationStatus::cases();

        return view('admin.reports.advanced', compact('institutions', 'statuses'));
    }

    public function data(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Application::class);

        $query = Application::query()
            ->with(['institution', 'creator'])
            ->select('applications.*');

        // ── Data isolation ────────────────────────────────────────────────
        $user = $request->user();
        if ($user->hasRole('field-team')) {
            $query->whereHas('fieldTasks', fn ($q) => $q->where('assigned_to', $user->id));
        } elseif ($user->hasRole(['institution-staff', 'institution-manager'])) {
            $query->where('institution_id', $user->institution_id);
        }

        $this->applyFilters($query, $request);

        $totalFiltered = (clone $query)->count();

        // DataTables ordering
        $orderCol   = $request->input('order.0.column', 0);
        $orderDir   = $request->input('order.0.dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $colMap     = ['id', 'application_no', 'applicant_last_name', 'institution_id', 'status', 'address_text', 'created_at', 'total_area_m2', 'total_price'];
        $orderField = $colMap[$orderCol] ?? 'created_at';

        $query->orderBy($orderField, $orderDir);

        $start  = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);
        $rows   = $query->offset($start)->limit($length)->get();

        $statusLabels = $this->statusLabels();

        $data = $rows->map(function (Application $app) use ($statusLabels) {
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
                $app->total_area_m2 ? number_format((float) $app->total_area_m2, 2) : '—',
                $app->total_price   ? '₺ ' . number_format((float) $app->total_price, 2) : '—',
            ];
        });

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => Application::count(),
            'recordsFiltered' => $totalFiltered,
            'data'            => $data,
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $this->authorize('viewAny', Application::class);

        $query = Application::query()->with(['institution', 'creator']);
        $this->applyFilters($query, $request);
        $applications = $query->orderByDesc('created_at')->get();

        $institutions  = Institution::query()->orderBy('name')->get();
        $filterSummary = $this->buildFilterSummary($request, $institutions);

        $pdf = Pdf::loadView('admin.reports.pdf', compact('applications', 'filterSummary'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('aykome-rapor-' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportCsv(Request $request): Response
    {
        $this->authorize('viewAny', Application::class);

        $query = Application::query()->with(['institution', 'creator']);
        $this->applyFilters($query, $request);
        $applications = $query->orderByDesc('created_at')->get();

        $statusLabels = $this->statusLabels();

        $output  = fopen('php://temp', 'r+b');
        // UTF-8 BOM for Excel Turkish character support
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'ID', 'Başvuru No', 'Ad Soyad', 'T.C. No', 'Kurum',
            'Durum', 'Adres', 'Başlangıç', 'Bitiş', 'Alan (m²)',
            'Toplam Tutar (₺)', 'Kayıt Tarihi',
        ], ';');

        foreach ($applications as $app) {
            $status = $app->status instanceof ApplicationStatus ? $app->status->value : (string) $app->status;
            [$label]  = $statusLabels[$status] ?? [$status];

            fputcsv($output, [
                $app->id,
                $app->application_no,
                trim($app->applicant_first_name . ' ' . $app->applicant_last_name),
                $app->applicant_national_id ?? $app->tc_no ?? '',
                $app->institution?->name ?? '',
                $label,
                $app->address_text ?? '',
                $app->start_date?->format('d.m.Y') ?? '',
                $app->end_date?->format('d.m.Y')   ?? '',
                $app->total_area_m2 ? number_format((float) $app->total_area_m2, 2, ',', '.') : '',
                $app->total_price   ? number_format((float) $app->total_price,   2, ',', '.') : '',
                $app->created_at?->format('d.m.Y H:i') ?? '',
            ], ';');
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return response($content, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="aykome-rapor-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function applyFilters(Builder $query, Request $request): void
    {
        // If explicit IDs are passed (selected rows), filter only those
        if ($ids = $request->input('ids')) {
            $query->whereIn('id', array_filter((array) $ids, 'is_numeric'));
            return; // IDs take priority — skip other filters
        }

        if ($from = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }
        if ($region = $request->input('region')) {
            $query->where('address_text', 'like', '%' . $region . '%');
        }
        if ($instId = $request->input('institution_id')) {
            $query->where('institution_id', $instId);
        }
        if ($statuses = $request->input('statuses')) {
            $query->whereIn('status', (array) $statuses);
        }
        // DataTables global search
        if ($search = $request->input('search.value')) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('application_no', 'like', "%{$search}%")
                  ->orWhere('applicant_first_name', 'like', "%{$search}%")
                  ->orWhere('applicant_last_name',  'like', "%{$search}%")
                  ->orWhere('address_text',          'like', "%{$search}%")
                  ->orWhereHas('institution', fn ($r) => $r->where('name', 'like', "%{$search}%"));
            });
        }
    }

    private function buildFilterSummary(Request $request, $institutions): array
    {
        $summary = [];
        if ($from = $request->input('date_from')) {
            $summary[] = 'Başlangıç: ' . $from;
        }
        if ($to = $request->input('date_to')) {
            $summary[] = 'Bitiş: ' . $to;
        }
        if ($region = $request->input('region')) {
            $summary[] = 'Bölge: ' . $region;
        }
        if ($instId = $request->input('institution_id')) {
            $inst = $institutions->firstWhere('id', $instId);
            $summary[] = 'Kurum: ' . ($inst?->name ?? $instId);
        }
        if ($statuses = $request->input('statuses')) {
            $labels = collect($this->statusLabels())
                ->only((array) $statuses)
                ->map(fn ($v) => $v[0])
                ->values()
                ->join(', ');
            $summary[] = 'Durum: ' . $labels;
        }
        return $summary;
    }

    private function statusLabels(): array
    {
        return [
            'draft'           => ['Taslak',           'bg-slate-100 text-slate-700'],
            'submitted'       => ['Gönderildi',        'bg-sky-100 text-sky-700'],
            'priced'          => ['Fiyatlandı',        'bg-indigo-100 text-indigo-700'],
            'awaiting_payment'=> ['Ödeme Bekliyor',    'bg-amber-100 text-amber-700'],
            'receipt_pending' => ['Makbuz Bekliyor',   'bg-orange-100 text-orange-700'],
            'approved'        => ['Onaylandı',         'bg-emerald-100 text-emerald-700'],
            'rejected'        => ['Reddedildi',        'bg-red-100 text-red-700'],
            'licensed'        => ['Ruhsatlandı',       'bg-teal-100 text-teal-700'],
            'field_work'      => ['Saha Çalışması',    'bg-violet-100 text-violet-700'],
            'completed'       => ['Tamamlandı',        'bg-green-100 text-green-700'],
            'archived'        => ['Arşivlendi',        'bg-gray-200 text-gray-600'],
        ];
    }
}
