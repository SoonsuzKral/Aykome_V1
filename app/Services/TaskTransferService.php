<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\FieldTask;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TaskTransferService
{
    public function __construct(
        protected ApplicationService $applicationService,
    ) {}

    public function assignFieldTask(Application $application, User $assignee, User $actor, ?string $notes = null, ?string $dueDate = null): FieldTask
    {
        return DB::transaction(function () use ($application, $assignee, $actor, $notes, $dueDate) {
            $task = $application->fieldTasks()->create([
                'assigned_to' => $assignee->id,
                'assigned_by' => $actor->id,
                'status' => 'pending',
                'due_date' => $dueDate,
                'notes' => $notes,
            ]);

            if ($application->status !== ApplicationStatus::FieldWork) {
                $application->update(['status' => ApplicationStatus::FieldWork]);
            }

            $this->applicationService->log($application, $actor, 'task.assigned', [
                'field_task_id' => $task->id,
                'assignee_id' => $assignee->id,
            ], 'Görev saha personeline devredildi');

            return $task;
        });
    }
}
