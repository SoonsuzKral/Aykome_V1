<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FieldTask;
use Illuminate\View\View;

class MyTasksController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $activeTasks = FieldTask::query()
            ->where('assigned_to', $user->id)
            ->whereIn('status', ['pending', 'in_progress'])
            ->with(['application:id,application_no,address_text,status'])
            ->orderByRaw("FIELD(status, 'in_progress', 'pending')")
            ->orderByDesc('updated_at')
            ->get();

        $completedTasks = FieldTask::query()
            ->where('assigned_to', $user->id)
            ->where('status', 'completed')
            ->with(['application:id,application_no,address_text,status'])
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        return view('admin.my-tasks.index', compact('activeTasks', 'completedTasks'));
    }
}
