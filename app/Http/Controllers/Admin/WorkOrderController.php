<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldTask;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class WorkOrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:pro.work_orders');
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $kanban = [
            'pending'     => FieldTask::query()->with(['application:id,application_no,address_text', 'assignee:id,name'])->where('status', 'pending')->latest()->limit(50)->get(),
            'in_progress' => FieldTask::query()->with(['application:id,application_no,address_text', 'assignee:id,name'])->where('status', 'in_progress')->latest()->limit(50)->get(),
            'completed'   => FieldTask::query()->with(['application:id,application_no,address_text', 'assignee:id,name'])->where('status', 'completed')->latest()->limit(20)->get(),
        ];

        $stats = [
            'total'       => FieldTask::query()->count(),
            'pending'     => FieldTask::query()->where('status', 'pending')->count(),
            'in_progress' => FieldTask::query()->where('status', 'in_progress')->count(),
            'completed'   => FieldTask::query()->where('status', 'completed')->count(),
            'overdue'     => FieldTask::query()->whereNotNull('due_date')->where('due_date', '<', now())->whereIn('status', ['pending', 'in_progress'])->count(),
        ];

        $fieldPersonnel = User::role('field-team')->get(['id', 'name']);

        return view('admin.work-orders.index', compact('kanban', 'stats', 'fieldPersonnel'));
    }

    public function data(Request $request): JsonResponse
    {
        $query = FieldTask::query()
            ->with(['application:id,application_no,address_text', 'assignee:id,name', 'assigner:id,name']);

        if ($search = $request->input('search.value')) {
            $query->whereHas('application', fn ($q) => $q->where('application_no', 'like', "%{$search}%")
                ->orWhere('address_text', 'like', "%{$search}%"));
        }

        if ($status = $request->input('filter_status')) {
            $query->where('status', $status);
        }

        $total = $query->count();

        $rows = $query
            ->orderByRaw("FIELD(status, 'in_progress', 'pending', 'completed')")
            ->orderBy('due_date')
            ->offset((int) $request->input('start', 0))
            ->limit((int) $request->input('length', 20))
            ->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $rows->map(fn ($t) => [
                $t->id,
                e($t->application?->application_no ?? '—'),
                e($t->application?->address_text ?? '—'),
                e($t->assignee?->name ?? '—'),
                $t->status,
                $t->due_date?->format('d.m.Y') ?? '—',
                // stage progress
                collect([
                    $t->stage_1_status ?? 'pending',
                    $t->stage_2_status ?? 'pending',
                    $t->stage_3_status ?? 'pending',
                ])->filter(fn ($s) => $s === 'done')->count(),
                $t->id,
            ]),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $statusMap = ['pending' => 'Bekleyen', 'in_progress' => 'Devam Ediyor', 'completed' => 'Tamamlandı'];
        $stageMap  = ['pending' => 'Bekliyor', 'in_progress' => 'Devam Ediyor', 'done' => 'Tamamlandı'];

        return response()->streamDownload(function () use ($statusMap, $stageMap) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($handle, ['#', 'Başvuru No', 'Adres', 'Atanan Kişi', 'Durum', 'Termin', 'Aşama 1', 'Aşama 2', 'Aşama 3'], ';');

            FieldTask::query()
                ->with(['application:id,application_no,address_text', 'assignee:id,name'])
                ->orderByRaw("FIELD(status, 'in_progress', 'pending', 'completed')")
                ->orderBy('due_date')
                ->each(function (FieldTask $task) use ($handle, $statusMap, $stageMap) {
                    fputcsv($handle, [
                        $task->id,
                        $task->application?->application_no ?? '—',
                        $task->application?->address_text ?? '—',
                        $task->assignee?->name ?? '—',
                        $statusMap[$task->status] ?? $task->status,
                        $task->due_date?->format('d.m.Y') ?? '—',
                        $stageMap[$task->stage_1_status ?? 'pending'],
                        $stageMap[$task->stage_2_status ?? 'pending'],
                        $stageMap[$task->stage_3_status ?? 'pending'],
                    ], ';');
                });

            fclose($handle);
        }, 'gorev-emirleri-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(): Response
    {
        $tasks = FieldTask::query()
            ->with(['application:id,application_no,address_text', 'assignee:id,name'])
            ->orderByRaw("FIELD(status, 'in_progress', 'pending', 'completed')")
            ->orderBy('due_date')
            ->get();

        $pdf = Pdf::loadView('admin.work-orders.export-pdf', compact('tasks'));
        $pdf->setPaper('A4', 'landscape');

        return $pdf->download('gorev-emirleri-' . now()->format('Y-m-d') . '.pdf');
    }
}
