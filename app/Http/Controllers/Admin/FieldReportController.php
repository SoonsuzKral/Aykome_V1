<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldTask;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class FieldReportController extends Controller
{
    public function __construct()
    {
        // Gelişmiş Saha Personel Raporu — ayrı satılabilir PRO modül
        $this->middleware('can:pro.field_reports');
    }

    public function index(Request $request): View
    {
        $now   = now();
        $start = $now->copy()->startOfMonth()->subMonths(5);

        /* ── Monthly completion trend (last 6 months) ── */
        $completedByMonth = FieldTask::query()
            ->selectRaw('EXTRACT(YEAR FROM updated_at) as yr, EXTRACT(MONTH FROM updated_at) as mo, COUNT(*) as cnt')
            ->where('status', 'completed')
            ->whereDate('updated_at', '>=', $start)
            ->groupBy(DB::raw('EXTRACT(YEAR FROM updated_at)'), DB::raw('EXTRACT(MONTH FROM updated_at)'))
            ->get()
            ->mapWithKeys(fn ($r) => [sprintf('%04d-%02d', $r->yr, $r->mo) => (int) $r->cnt]);

        $months      = collect(range(0, 5))->map(fn ($i) => $start->copy()->addMonths($i));
        $trMonths    = ['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara'];
        $chartLabels = $months->map(fn ($d) => $trMonths[(int)$d->format('n') - 1].' '.$d->format('Y'))->values()->toJson();
        $chartData   = $months->map(fn ($d) => $completedByMonth[$d->format('Y-m')] ?? 0)->values()->toJson();

        /* ── Stage completion rates ── */
        $total      = FieldTask::query()->count() ?: 1;
        $stageStats = [
            ['label' => 'Kazı Öncesi Kontrol',   'done' => FieldTask::query()->where('stage_1_status', 'done')->count()],
            ['label' => 'Kazı Sonrası Kontrol',   'done' => FieldTask::query()->where('stage_2_status', 'done')->count()],
            ['label' => 'Zemin Onarım Kontrolü',  'done' => FieldTask::query()->where('stage_3_status', 'done')->count()],
        ];
        foreach ($stageStats as &$s) {
            $s['rate'] = round($s['done'] / $total * 100, 1);
        }
        unset($s);

        /* ── Active task map: assigned_to → application_no ── */
        $activeTaskMap = FieldTask::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->with('application:id,application_no')
            ->get()
            ->groupBy('assigned_to')
            ->map(fn ($tasks) => optional($tasks->sortByDesc('updated_at')->first()->application)->application_no);

        /* ── Personnel performance ── */
        $personnel = User::role('field-team')
            ->withCount([
                'fieldTasksAssigned as total_tasks',
                'fieldTasksAssigned as completed_tasks' => fn ($q) => $q->where('status', 'completed'),
                'fieldTasksAssigned as pending_tasks'   => fn ($q) => $q->where('status', 'pending'),
                'fieldTasksAssigned as active_tasks'    => fn ($q) => $q->where('status', 'in_progress'),
                'fieldTasksAssigned as overdue_tasks'   => fn ($q) => $q
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now()),
            ])
            ->get()
            ->map(function (User $user) use ($activeTaskMap) {
                $user->success_rate = $user->total_tasks > 0
                    ? round($user->completed_tasks / $user->total_tasks * 100, 1)
                    : 0;

                $user->delay_rate = $user->total_tasks > 0
                    ? round($user->overdue_tasks / $user->total_tasks * 100, 1)
                    : 0;

                /* Badge rengi: ≥80 yeşil, ≥50 amber, <50 kırmızı */
                $user->perf_level = match (true) {
                    $user->success_rate >= 80 => 'high',
                    $user->success_rate >= 50 => 'mid',
                    default                   => 'low',
                };

                /* Aktif görev varsa başvuru no'sunu ekle */
                $user->active_application_no = $activeTaskMap->get($user->id);

                return $user;
            })
            ->sortByDesc('success_rate');

        /* ── Overall stats ── */
        $overallStats = [
            'total'       => FieldTask::query()->count(),
            'completed'   => FieldTask::query()->where('status', 'completed')->count(),
            'in_progress' => FieldTask::query()->where('status', 'in_progress')->count(),
            'overdue'     => FieldTask::query()
                ->whereIn('status', ['pending', 'in_progress'])
                ->whereNotNull('due_date')
                ->where('due_date', '<', now())
                ->count(),
        ];

        return view('admin.field-reports-pro.index', compact(
            'chartLabels', 'chartData', 'stageStats', 'personnel', 'overallStats'
        ));
    }

    public function exportCsv(): StreamedResponse
    {
        $personnel = $this->buildPersonnel();

        return response()->streamDownload(function () use ($personnel) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Personel', 'E-posta', 'Toplam Görev', 'Tamamlanan', 'Geciken', 'Başarı Oranı (%)', 'Gecikme Oranı (%)', 'Performans'], ';');

            foreach ($personnel as $p) {
                $perf = match ($p->perf_level) {
                    'high'  => 'Yüksek',
                    'mid'   => 'Orta',
                    default => 'Düşük',
                };
                fputcsv($handle, [
                    $p->name,
                    $p->email,
                    $p->total_tasks,
                    $p->completed_tasks,
                    $p->overdue_tasks,
                    $p->success_rate,
                    $p->delay_rate,
                    $perf,
                ], ';');
            }

            fclose($handle);
        }, 'saha-personel-raporu-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(): Response
    {
        $personnel = $this->buildPersonnel();
        $pdf = Pdf::loadView('admin.field-reports-pro.export-pdf', compact('personnel'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('saha-personel-raporu-' . now()->format('Y-m-d') . '.pdf');
    }

    private function buildPersonnel()
    {
        $activeTaskMap = FieldTask::query()
            ->whereIn('status', ['pending', 'in_progress'])
            ->with('application:id,application_no')
            ->get()
            ->groupBy('assigned_to')
            ->map(fn ($tasks) => optional($tasks->sortByDesc('updated_at')->first()->application)->application_no);

        return User::role('field-team')
            ->withCount([
                'fieldTasksAssigned as total_tasks',
                'fieldTasksAssigned as completed_tasks' => fn ($q) => $q->where('status', 'completed'),
                'fieldTasksAssigned as pending_tasks'   => fn ($q) => $q->where('status', 'pending'),
                'fieldTasksAssigned as active_tasks'    => fn ($q) => $q->where('status', 'in_progress'),
                'fieldTasksAssigned as overdue_tasks'   => fn ($q) => $q
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now()),
            ])
            ->get()
            ->map(function (User $user) use ($activeTaskMap) {
                $user->success_rate = $user->total_tasks > 0
                    ? round($user->completed_tasks / $user->total_tasks * 100, 1)
                    : 0;
                $user->delay_rate = $user->total_tasks > 0
                    ? round($user->overdue_tasks / $user->total_tasks * 100, 1)
                    : 0;
                $user->perf_level = match (true) {
                    $user->success_rate >= 80 => 'high',
                    $user->success_rate >= 50 => 'mid',
                    default                   => 'low',
                };
                $user->active_application_no = $activeTaskMap->get($user->id);

                return $user;
            })
            ->sortByDesc('success_rate');
    }
}
