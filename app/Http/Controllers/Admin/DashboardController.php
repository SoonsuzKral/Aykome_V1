<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationTimelineLog;
use App\Models\FieldTask;
use App\Models\License;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // Super Admin — platform-wide license & firm overview
        if ($user->hasRole('super-admin')) {
            return $this->superAdminDashboard($user);
        }

        // Field team / institution staff get a focused lightweight dashboard
        if ($user->hasRole(['field-team', 'institution-staff', 'institution-manager'])) {
            return $this->fieldDashboard($user);
        }

        // Municipality / Admin full dashboard
        return $this->adminDashboard($user);
    }

    private function superAdminDashboard($user): View
    {
        $now = now();

        $allLicenses = License::query()->with('institution:id,name')->get();

        $stats = [
            'total'          => $allLicenses->count(),
            'active'         => $allLicenses->filter(fn ($l) => $l->is_active && $l->valid_until >= $now->toDateString())->count(),
            'expiring_soon'  => $allLicenses->filter(fn ($l) => $l->is_active
                && $l->valid_until >= $now->toDateString()
                && $l->valid_until <= $now->copy()->addDays(30)->toDateString()
            )->count(),
            'expired'        => $allLicenses->filter(fn ($l) => $l->valid_until < $now->toDateString())->count(),
            'locked'         => $allLicenses->filter(fn ($l) => ! $l->is_active)->count(),
            'total_users'    => User::query()->count(),
            'applications'   => Application::query()->count(),
            'revenue'        => round((float) Application::query()->where('payment_status', 'paid')->sum('total_price'), 3),
        ];

        $criticalLicenses = $allLicenses
            ->filter(fn ($l) => $l->is_active
                && $l->valid_until >= $now->toDateString()
                && $l->valid_until <= $now->copy()->addDays(30)->toDateString()
            )
            ->sortBy('valid_until')
            ->values();

        $expiredLicenses = $allLicenses
            ->filter(fn ($l) => $l->valid_until < $now->toDateString())
            ->sortBy('valid_until')
            ->values();

        $recentApplications = Application::query()
            ->with(['institution:id,name'])
            ->latest()
            ->limit(8)
            ->get(['id', 'application_no', 'institution_id', 'status', 'created_at', 'total_price']);

        return view('admin.dashboard-superadmin', compact(
            'stats', 'allLicenses', 'criticalLicenses', 'expiredLicenses', 'recentApplications'
        ));
    }

    private function adminDashboard($user): View
    {
        $now = now();
        $startOfSixMonthWindow = $now->copy()->startOfMonth()->subMonths(5);

        $months = collect(range(0, 5))
            ->map(fn (int $offset) => $startOfSixMonthWindow->copy()->addMonths($offset));

        $applicationBuckets = Application::query()
            ->selectRaw("EXTRACT(YEAR FROM \"CREATED_AT\") as year_no, EXTRACT(MONTH FROM \"CREATED_AT\") as month_no, COUNT(*) as total")
            ->whereDate('created_at', '>=', $startOfSixMonthWindow)
            ->groupByRaw('EXTRACT(YEAR FROM "CREATED_AT"), EXTRACT(MONTH FROM "CREATED_AT")')
            ->get()
            ->mapWithKeys(fn ($row) => [sprintf('%04d-%02d', $row->year_no, $row->month_no) => (int) $row->total]);

        $revenueBuckets = Application::query()
            ->selectRaw("EXTRACT(YEAR FROM \"RECEIPT_APPROVED_AT\") as year_no, EXTRACT(MONTH FROM \"RECEIPT_APPROVED_AT\") as month_no, SUM(\"TOTAL_PRICE\") as total")
            ->where('payment_status', 'paid')
            ->whereNotNull('receipt_approved_at')
            ->whereDate('receipt_approved_at', '>=', $startOfSixMonthWindow)
            ->groupByRaw('EXTRACT(YEAR FROM "RECEIPT_APPROVED_AT"), EXTRACT(MONTH FROM "RECEIPT_APPROVED_AT")')
            ->get()
            ->mapWithKeys(fn ($row) => [sprintf('%04d-%02d', $row->year_no, $row->month_no) => (float) $row->total]);

        $chartLabels = $months
            ->map(fn ($date) => $date->translatedFormat('M Y'))
            ->values();

        $chartApplicationSeries = $months
            ->map(fn ($date) => (int) ($applicationBuckets[$date->format('Y-m')] ?? 0))
            ->values();

        $chartRevenueSeries = $months
            ->map(fn ($date) => round((float) ($revenueBuckets[$date->format('Y-m')] ?? 0), 2))
            ->values();

        $recentActivities = ApplicationTimelineLog::query()
            ->with(['user:id,name', 'application:id,application_no'])
            ->latest()
            ->limit(10)
            ->get();

        $recentApplications = Application::query()
            ->with(['institution:id,name'])
            ->latest()
            ->limit(6)
            ->get(['id', 'application_no', 'institution_id', 'status', 'created_at', 'total_price']);

        return view('admin.dashboard', [
            'stats' => [
                'applications_total' => Application::query()->count(),
                'applications_pending' => Application::query()->where('approval_status', 'pending')->count(),
                'applications_this_month' => Application::query()
                    ->whereBetween('created_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                    ->count(),
                'paid_revenue_total' => round((float) Application::query()->where('payment_status', 'paid')->sum('total_price'), 3),
                'awaiting_payment_total' => Application::query()
                    ->whereIn('status', [ApplicationStatus::AwaitingPayment->value, ApplicationStatus::ReceiptPending->value])
                    ->count(),
            ],
            'chart' => [
                'labels' => $chartLabels,
                'applications' => $chartApplicationSeries,
                'revenues' => $chartRevenueSeries,
            ],
            'recentActivities' => $recentActivities,
            'recentApplications' => $recentApplications,
        ]);
    }

    private function fieldDashboard($user): View
    {
        $myTasks = FieldTask::query()
            ->with(['application:id,application_no,address_text,status'])
            ->where('assigned_to', $user->id)
            ->orderByRaw("DECODE(status, 'in_progress', 1, 'pending', 2, 'completed', 3, 4)")
            ->latest()
            ->limit(20)
            ->get();

        $myApplications = Application::query()
            ->where('created_by', $user->id)
            ->orWhere('institution_id', $user->institution_id)
            ->latest()
            ->limit(10)
            ->get(['id', 'application_no', 'status', 'address_text', 'created_at']);

        $taskStats = [
            'pending' => FieldTask::query()->where('assigned_to', $user->id)->where('status', 'pending')->count(),
            'in_progress' => FieldTask::query()->where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
            'completed' => FieldTask::query()->where('assigned_to', $user->id)->where('status', 'completed')->count(),
        ];

        return view('admin.dashboard-field', [
            'myTasks' => $myTasks,
            'myApplications' => $myApplications,
            'taskStats' => $taskStats,
            'user' => $user,
        ]);
    }
}
