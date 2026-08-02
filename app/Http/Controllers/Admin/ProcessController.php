<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessDefinition;
use App\Models\ProcessStep;
use App\Services\ProcessEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Süreç ve Onay Rotası (Hiyerarşi Yönetim Modülü)
 * ------------------------------------------------
 * SADECE belediye merkez yönetimi (super-admin + municipality-admin) erişir.
 * Alt kurumlar bu modüle giremez (403). Tanımlanan silsile adımlarına göre
 * başvurular adım adım ilerler.
 */
class ProcessController extends Controller
{
    public function __construct(
        protected ProcessEngine $engine,
    ) {
        $this->middleware(function (Request $request, $next) {
            if (! $request->user()?->hasAnyRole(['super-admin', 'municipality-admin'])) {
                abort(403, 'Bu modül yalnızca belediye merkez yönetimine açıktır.');
            }

            return $next($request);
        });
    }

    public function index(): View
    {
        return view('admin.processes.index', [
            'processes' => ProcessDefinition::query()->with('steps')->orderBy('id')->get(),
            'roleOptions' => $this->engine->roleOptions(),
            'moduleOptions' => $this->engine->moduleOptions(),
            'topRoles' => ProcessEngine::TOP_ROLES,
        ]);
    }

    public function storeDefinition(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $slug = $data['slug'] ?: Str::slug($data['name']) . '-' . Str::lower(Str::random(4));

        if (ProcessDefinition::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['slug' => 'Bu süreç adresi zaten kullanılıyor.']);
        }

        if (! empty($data['is_default'])) {
            ProcessDefinition::query()->update(['is_default' => false]);
        }

        $process = ProcessDefinition::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'is_active' => true,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', "Süreç \"{$process->name}\" oluşturuldu. Şimdi silsile adımlarını ekleyin.");
    }

    public function storeStep(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'process_definition_id' => ['required', 'exists:process_definitions,id'],
            'name' => ['required', 'string', 'max:190'],
            'role_key' => ['required', 'string', 'max:50', 'alpha_dash'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string'],
            'approvable_modules' => ['required', 'array', 'min:1'],
            'approvable_modules.*' => ['required', 'string'],
        ]);

        $process = ProcessDefinition::query()->findOrFail($data['process_definition_id']);

        if (ProcessStep::query()
            ->where('process_definition_id', $process->id)
            ->where('role_key', $data['role_key'])
            ->exists()) {
            throw ValidationException::withMessages(['role_key' => 'Bu adım anahtarı zaten kullanılıyor.']);
        }

        $maxOrder = (int) ProcessStep::query()
            ->where('process_definition_id', $process->id)
            ->max('step_order');

        ProcessStep::query()->create([
            'process_definition_id' => $process->id,
            'name' => $data['name'],
            'role_key' => $data['role_key'],
            'roles' => array_values($data['roles']),
            'approvable_modules' => array_values($data['approvable_modules']),
            'step_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        return back()->with('success', "Adım \"{$data['name']}\" silsileye eklendi.");
    }

    public function updateStep(Request $request, ProcessStep $step): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'role_key' => ['required', 'string', 'max:50', 'alpha_dash'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string'],
            'approvable_modules' => ['required', 'array', 'min:1'],
            'approvable_modules.*' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $duplicate = ProcessStep::query()
            ->where('process_definition_id', $step->process_definition_id)
            ->where('role_key', $data['role_key'])
            ->whereKeyNot($step->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['role_key' => 'Bu adım anahtarı zaten kullanılıyor.']);
        }

        $step->update([
            'name' => $data['name'],
            'role_key' => $data['role_key'],
            'roles' => array_values($data['roles']),
            'approvable_modules' => array_values($data['approvable_modules']),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', "Adım \"{$step->name}\" güncellendi.");
    }

    public function destroyStep(ProcessStep $step): RedirectResponse
    {
        $name = $step->name;
        $step->delete();

        return back()->with('success', "Adım \"{$name}\" silindi.");
    }

    public function reorderStep(ProcessStep $step, string $direction): RedirectResponse
    {
        $steps = ProcessStep::query()
            ->where('process_definition_id', $step->process_definition_id)
            ->orderBy('step_order')
            ->orderBy('id')
            ->get();

        $index = $steps->search(fn (ProcessStep $s) => $s->id === $step->id);
        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
        $swap = $steps->get($swapIndex);

        if ($swap) {
            $tmp = $step->step_order;
            $step->update(['step_order' => $swap->step_order]);
            $swap->update(['step_order' => $tmp]);
        }

        return back()->with('success', 'Silsile sırası güncellendi.');
    }

    public function setDefault(ProcessDefinition $process): RedirectResponse
    {
        ProcessDefinition::query()->update(['is_default' => false]);
        $process->update(['is_default' => true, 'is_active' => true]);

        return back()->with('success', "\"{$process->name}\" varsayılan aktif süreç olarak seçildi.");
    }

    public function toggleActive(ProcessDefinition $process): RedirectResponse
    {
        $process->update(['is_active' => ! $process->is_active]);

        return back()->with('success', "\"{$process->name}\" " . ($process->is_active ? 'aktifleştirildi' : 'pasife alındı') . '.');
    }
}
