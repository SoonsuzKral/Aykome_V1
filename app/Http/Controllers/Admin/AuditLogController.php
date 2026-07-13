<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        $stats = [
            'total'   => AuditLog::query()->count(),
            'today'   => AuditLog::query()->whereDate('created_at', today())->count(),
            'logins'  => AuditLog::query()->where('action', 'auth.login')->whereDate('created_at', today())->count(),
            'actions' => AuditLog::query()->whereDate('created_at', today())->where('action', '!=', 'auth.login')->count(),
        ];

        return view('admin.logs.index', compact('stats'));
    }

    /**
     * DataTables server-side AJAX endpoint.
     */
    public function data(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('super-admin'), 403);

        $query = AuditLog::query()->orderByDesc('created_at');

        // Global search
        $search = trim((string) $request->input('search.value', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%")
                  ->orWhere('subject_type', 'like', "%{$search}%");
            });
        }

        // Action filter (custom param)
        $actionFilter = trim((string) $request->input('action_filter', ''));
        if ($actionFilter !== '') {
            $query->where('action', 'like', "{$actionFilter}%");
        }

        $total    = AuditLog::query()->count();
        $filtered = $query->count();

        $data = $query
            ->offset((int) $request->input('start', 0))
            ->limit(max(1, min((int) $request->input('length', 25), 100)))
            ->get();

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data->map(fn (AuditLog $log) => [
                'id'           => $log->id,
                'user_name'    => $log->user_name ?? '<span class="text-slate-400">—</span>',
                'user_role'    => $log->user_role ?? '',
                'action'       => $log->action,
                'action_label' => AuditLog::actionLabel($log->action),
                'badge_class'  => AuditLog::actionBadgeClass($log->action),
                'description'  => $log->description,
                'subject'      => $log->subject_type ? "{$log->subject_type} #{$log->subject_id}" : '—',
                'ip_address'   => $log->ip_address ?? '—',
                'created_at'   => $log->created_at?->format('d.m.Y H:i:s') ?? '—',
            ])->values(),
        ]);
    }
}
