<?php

namespace App\Http\Controllers\Admin;

use App\Events\FieldTaskCompleted;
use App\Http\Controllers\Controller;
use App\Models\FieldTask;
use App\Models\User;
use App\Notifications\FieldStageCompleted;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class FieldTaskController extends Controller
{
    private const STEPS = [
        'pre_dig'     => 'Kazı Öncesi',
        'post_dig'    => 'Kazı Sonrası',
        'post_repair' => 'Zemin Onarım Sonrası',
    ];

    public function show(Request $request, FieldTask $fieldTask): View
    {
        $user = $request->user();

        $fieldTask->load([
            'application.institution',
            'application.timelineLogs',
            'assignee',
            'assigner',
            'stepMedia',
        ]);

        $isAssignee = $user->id === $fieldTask->assigned_to;
        $canManage  = $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])
                      || $isAssignee;

        if (! $canManage) {
            abort(403);
        }

        $mediaByStep = $fieldTask->stepMedia->groupBy('step');

        return view('admin.field-tasks.show', [
            'fieldTask'   => $fieldTask,
            'application' => $fieldTask->application,
            'steps'       => self::STEPS,
            'mediaByStep' => $mediaByStep,
            'isAssignee'  => $isAssignee,
        ]);
    }

    public function addMedia(Request $request, FieldTask $fieldTask): RedirectResponse
    {
        $user = $request->user();

        $isAssignee = $user->id === $fieldTask->assigned_to;
        $canUpload  = $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])
                      || $isAssignee;

        if (! $canUpload) {
            abort(403);
        }

        $validated = $request->validate([
            'step'    => ['required', Rule::in(array_keys(self::STEPS))],
            'photo'   => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:500'],
        ]);

        $path = $request->file('photo')->store(
            'field-tasks/' . $fieldTask->id . '/' . $validated['step'],
            'public'
        );

        $fieldTask->stepMedia()->create([
            'step'       => $validated['step'],
            'image_path' => $path,
            'caption'    => $validated['caption'] ?? null,
        ]);

        return back()->with('success', self::STEPS[$validated['step']] . ' fotoğrafı başarıyla yüklendi!');
    }

    public function updateStatus(Request $request, FieldTask $fieldTask): RedirectResponse
    {
        $user = $request->user();

        $isAssignee = $user->id === $fieldTask->assigned_to;
        $canUpdate  = $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])
                      || $isAssignee;

        if (! $canUpdate) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])],
        ]);

        $fieldTask->update(['status' => $validated['status']]);

        // Görev tamamlandığında admin-notifications kanalına broadcast
        if ($validated['status'] === 'completed') {
            Log::info('[FieldTaskController] updateStatus → FieldTaskCompleted dispatch edildi.', ['task_id' => $fieldTask->id]);
            try {
                FieldTaskCompleted::dispatch($fieldTask->load(['application', 'assignee']));
            } catch (\Throwable $e) {
                Log::error('[FieldTaskController] updateStatus broadcast hatası: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Görev durumu güncellendi.');
    }

    public function inspect(Request $request, FieldTask $fieldTask): View
    {
        $user = $request->user();

        $isAssignee = $user->id === $fieldTask->assigned_to;
        $canAccess  = $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])
                      || $isAssignee;

        if (! $canAccess) {
            abort(403);
        }

        $fieldTask->load(['application.institution', 'assignee', 'stepMedia']);
        $mediaByStep = $fieldTask->stepMedia->groupBy('step');

        return view('admin.field-tasks.inspect', [
            'fieldTask'   => $fieldTask,
            'application' => $fieldTask->application,
            'mediaByStep' => $mediaByStep,
            'isAssignee'  => $isAssignee,
            'steps'       => self::STEPS,
        ]);
    }

    public function updateStage(Request $request, FieldTask $fieldTask): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $isAssignee = $user->id === $fieldTask->assigned_to;
        $canUpdate  = $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff'])
                      || $isAssignee;

        if (! $canUpdate) {
            abort(403);
        }

        $validated = $request->validate([
            'stage'  => ['required', Rule::in([1, 2, 3])],
            'status' => ['required', Rule::in(['pending', 'done'])],
            'notes'  => ['nullable', 'string', 'max:2000'],
        ]);

        $stage = (int) $validated['stage'];
        $isCompletingStage = $validated['status'] === 'done';

        $fieldTask->update([
            "stage_{$stage}_status"       => $validated['status'],
            "stage_{$stage}_notes"        => $validated['notes'] ?? null,
            "stage_{$stage}_inspected_at" => $isCompletingStage ? now() : null,
        ]);

        $fieldTask->refresh();

        // Otomatik görev durumu güncelleme
        $allStagesDone = $fieldTask->stage_1_status === 'done'
                      && $fieldTask->stage_2_status === 'done'
                      && $fieldTask->stage_3_status === 'done';

        if ($allStagesDone) {
            $fieldTask->update(['status' => 'completed']);

            // Tüm aşamalar tamamlandı → admin-notifications kanalına broadcast
            Log::info('[FieldTaskController] updateStage → tüm aşamalar done, FieldTaskCompleted dispatch edildi.', ['task_id' => $fieldTask->id]);
            try {
                FieldTaskCompleted::dispatch($fieldTask->load(['application', 'assignee']));
            } catch (\Throwable $e) {
                Log::error('[FieldTaskController] updateStage broadcast hatası: ' . $e->getMessage());
            }
        } elseif ($fieldTask->stage_1_status === 'done' || $fieldTask->stage_2_status === 'done') {
            if ($fieldTask->status === 'pending') {
                $fieldTask->update(['status' => 'in_progress']);
            }
        }

        // Aşama tamamlandığında yöneticileri + kurum çalışanlarını bildir
        if ($isCompletingStage) {
            try {
                $application = $fieldTask->application;
                $admins = User::query()
                    ->where(function ($q) use ($application) {
                        $q->role(['super-admin', 'municipality-admin', 'municipality-staff']);
                        if ($application && $application->institution_id) {
                            $q->orWhere('institution_id', $application->institution_id);
                        }
                    })
                    ->where('id', '!=', $request->user()->id)
                    ->get();
                $notification = new FieldStageCompleted($fieldTask, $request->user(), $stage);
                foreach ($admins as $admin) {
                    $admin->notify($notification);
                }
            } catch (\Throwable) {
                // Bildirim hatası ana akışı durdurmasın
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'task_status' => $fieldTask->fresh()->status]);
        }

        $stageLabel = ['Kazı Öncesi', 'Kazı Sonrası', 'Zemin Onarım'][$stage - 1] ?? "Aşama {$stage}";
        $message = $isCompletingStage
            ? "{$stageLabel} aşaması tamamlandı olarak işaretlendi!"
            : "{$stageLabel} aşaması güncellendi.";

        return back()->with('success', $message);
    }
}
