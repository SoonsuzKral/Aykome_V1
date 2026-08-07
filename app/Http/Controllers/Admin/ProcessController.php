<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessDefinition;
use App\Models\ProcessStep;
use App\Models\User;
use App\Services\ProcessEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;

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

        $slug = ($data['slug'] ?? null) ?: Str::slug($data['name']) . '-' . Str::lower(Str::random(4));

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

    public function updateDefinition(Request $request, ProcessDefinition $process): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'slug' => ['nullable', 'string', 'max:190', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $slug = $data['slug'] ?? $process->slug;

        // Check slug uniqueness only if slug is being changed
        if ($slug !== $process->slug) {
            $exists = ProcessDefinition::query()
                ->where('slug', $slug)
                ->whereKeyNot($process->id)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages(['slug' => 'Bu süreç adresi zaten kullanılıyor.']);
            }
        }

        $process->update([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
        ]);

        return back()->with('success', "Süreç adı güncellendi.");
    }

    // ─── Blueprint Canvas ──────────────────────────────────────────────────────

    public function blueprint(ProcessDefinition $process)
    {
        $process->load('steps');

        // Personnel options: municipality personnel who can be assigned to steps
        $personnelOptions = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'like', 'municipality-%'))
            ->orWhereHas('roles', fn ($q) => $q->where('name', 'super-admin'))
            ->with('roles')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'roles' => $u->roles->pluck('name')->toArray(),
            ]);

        return Inertia::render('Admin/Processes/BlueprintCanvas', [
            'process' => $process,
            'steps' => $process->steps->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'role_key' => $s->role_key,
                'roles' => $s->roles ?? [],
                'approvable_modules' => $s->approvable_modules ?? [],
                'module_permissions' => $s->module_permissions ?? [],
                'personnel_ids' => $s->personnel_ids ?? [],
                'visibility_config' => $s->visibility_config ?? [],
                'approval_config' => $s->approval_config ?? ['mode' => 'any'],
                'signature_config' => $s->signature_config ?? null,
                'action_type' => $s->action_type ?? 'onay',
                'step_order' => $s->step_order,
                'is_active' => $s->is_active,
                'canvas_x' => $s->canvas_x ?? 100 + $s->step_order * 40,
                'canvas_y' => $s->canvas_y ?? 100 + $s->step_order * 40,
            ]),
            'connections' => $process->canvas_connections ?? [],
            'roleOptions' => $this->engine->roleOptions(),
            'moduleOptions' => $this->engine->moduleOptions(),
            'personnelOptions' => $personnelOptions,
        ]);
    }

    public function saveCanvas(Request $request, ProcessDefinition $process): RedirectResponse
    {
        $data = $request->validate([
            'steps' => ['required', 'array'],
            'steps.*.id' => ['required', 'exists:process_steps,id'],
            'steps.*.canvas_x' => ['required', 'integer'],
            'steps.*.canvas_y' => ['required', 'integer'],
            'connections' => ['required', 'array'],
        ]);

        // Save node positions
        foreach ($data['steps'] as $stepData) {
            ProcessStep::query()->where('id', $stepData['id'])->update([
                'canvas_x' => $stepData['canvas_x'],
                'canvas_y' => $stepData['canvas_y'],
            ]);
        }

        // Save connections
        $process->update(['canvas_connections' => $data['connections']]);

        return back()->with('success', 'Canvas kaydedildi.');
    }

    public function publish(Request $request, ProcessDefinition $process): RedirectResponse
    {
        // Archive old published version
        ProcessDefinition::query()
            ->where('slug', $process->slug)
            ->whereNotNull('published_at')
            ->update(['status' => 'archived']);

        // Publish this version
        $process->update([
            'version' => ($process->version ?? 0) + 1,
            'status' => 'published',
            'published_at' => now(),
        ]);

        return back()->with('success', "İş akışı v{$process->version} olarak yayınlandı.");
    }

    public function storeStep(Request $request): RedirectResponse|array
    {
        $data = $request->validate([
            'process_definition_id' => ['required', 'exists:process_definitions,id'],
            'name' => ['required', 'string', 'max:190'],
            'role_key' => ['nullable', 'string', 'max:50', 'alpha_dash'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['required', 'string'],
            'approvable_modules' => ['nullable', 'array'],
            'approvable_modules.*' => ['required', 'string'],
            'module_permissions' => ['nullable', 'array'],
            'personnel_ids' => ['nullable', 'array'],
            'personnel_ids.*' => ['required', 'integer', 'exists:users,id'],
            'visibility_config' => ['nullable', 'array'],
            'visibility_config.*' => ['required', 'string'],
            'approval_config' => ['nullable', 'array'],
            'approval_config.mode' => ['nullable', 'string', 'in:any,all,assigned_only'],
            'signature_config' => ['nullable', 'array'],
            'signature_config.enabled' => ['nullable', 'boolean'],
            'signature_config.signer_ids' => ['nullable', 'array'],
            'signature_config.signer_ids.*' => ['nullable', 'integer'],
            'signature_config.signer_roles' => ['nullable', 'array'],
            'signature_config.signer_roles.*' => ['nullable', 'string'],
            'signature_config.pdf_type' => ['nullable', 'string', 'in:ruhsat,metraj,tahakkuk,taahhutname,makbuz,pre_permit,cover_letter'],
            'signature_config.delegation' => ['nullable', 'array'],
            'signature_config.delegation.allowed' => ['nullable', 'boolean'],
            'signature_config.delegation.delegable_to' => ['nullable', 'array'],
            'signature_config.delegation.delegable_to.*' => ['nullable', 'string'],
            'signature_config.delegation.delegable_ids' => ['nullable', 'array'],
            'signature_config.delegation.delegable_ids.*' => ['nullable', 'integer'],
            'signature_config.delegation.requires_approval' => ['nullable', 'boolean'],
            'action_type' => ['nullable', 'string', 'in:onay,paraf,e_imza'],
            'is_active' => ['nullable', 'boolean'],
            'canvas_x' => ['nullable', 'integer'],
            'canvas_y' => ['nullable', 'integer'],
            'step_order' => ['nullable', 'integer'],
        ]);

        $process = ProcessDefinition::query()->findOrFail($data['process_definition_id']);

        // Auto-derive role_key from first selected personnel's roles if not provided
        $roleKey = $data['role_key'];
        if (empty($roleKey) && ! empty($data['personnel_ids'])) {
            $firstUser = User::query()->with('roles')->find($data['personnel_ids'][0]);
            if ($firstUser && $firstUser->roles->isNotEmpty()) {
                $roleKey = $firstUser->roles->first()->name;
            }
        }

        // Auto-derive roles from selected personnel's roles if not provided
        $derivedRoles = $data['roles'] ?? [];
        if (empty($derivedRoles) && ! empty($data['personnel_ids'])) {
            $users = User::query()->with('roles')->whereIn('id', $data['personnel_ids'])->get();
            foreach ($users as $user) {
                foreach ($user->roles as $role) {
                    if (! in_array($role->name, $derivedRoles, true)) {
                        $derivedRoles[] = $role->name;
                    }
                }
            }
        }

        $maxOrder = (int) ProcessStep::query()
            ->where('process_definition_id', $process->id)
            ->max('step_order');

        $step = ProcessStep::query()->create([
            'process_definition_id' => $process->id,
            'name' => $data['name'],
            'role_key' => $roleKey ?? 'step-' . ($maxOrder + 1),
            'roles' => array_values($derivedRoles),
            'approvable_modules' => array_values($data['approvable_modules'] ?? []),
            'module_permissions' => $data['module_permissions'] ?? null,
            'personnel_ids' => $data['personnel_ids'] ? array_values($data['personnel_ids']) : [],
            'visibility_config' => $data['visibility_config'] ? array_values($data['visibility_config']) : [],
            'approval_config' => $data['approval_config'] ?? ['mode' => 'any'],
            'signature_config' => $data['signature_config'] ?? null,
            'action_type' => $data['action_type'] ?? 'onay',
            'step_order' => $data['step_order'] ?? ($maxOrder + 1),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'canvas_x' => $data['canvas_x'] ?? 100,
            'canvas_y' => $data['canvas_y'] ?? 100,
        ]);

        // For Inertia requests, redirect back with flash data
        // The new_step will be available in page.props.flash.new_step on the client
        return back()->with('new_step', $step);

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
            'module_permissions' => ['nullable', 'array'],
            'personnel_ids' => ['nullable', 'array'],
            'personnel_ids.*' => ['required', 'integer', 'exists:users,id'],
            'visibility_config' => ['nullable', 'array'],
            'visibility_config.*' => ['required', 'string'],
            'approval_config' => ['nullable', 'array'],
            'approval_config.mode' => ['nullable', 'string', 'in:any,all,assigned_only'],
            'signature_config' => ['nullable', 'array'],
            'signature_config.enabled' => ['nullable', 'boolean'],
            'signature_config.signer_ids' => ['nullable', 'array'],
            'signature_config.signer_ids.*' => ['nullable', 'integer'],
            'signature_config.signer_roles' => ['nullable', 'array'],
            'signature_config.signer_roles.*' => ['nullable', 'string'],
            'signature_config.pdf_type' => ['nullable', 'string', 'in:ruhsat,metraj,tahakkuk,taahhutname,makbuz,pre_permit,cover_letter'],
            'signature_config.delegation' => ['nullable', 'array'],
            'signature_config.delegation.allowed' => ['nullable', 'boolean'],
            'signature_config.delegation.delegable_to' => ['nullable', 'array'],
            'signature_config.delegation.delegable_to.*' => ['nullable', 'string'],
            'signature_config.delegation.delegable_ids' => ['nullable', 'array'],
            'signature_config.delegation.delegable_ids.*' => ['nullable', 'integer'],
            'signature_config.delegation.requires_approval' => ['nullable', 'boolean'],
            'action_type' => ['nullable', 'string', 'in:onay,paraf,e_imza'],
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
            'module_permissions' => $data['module_permissions'] ?? null,
            'personnel_ids' => $data['personnel_ids'] ? array_values($data['personnel_ids']) : [],
            'visibility_config' => $data['visibility_config'] ? array_values($data['visibility_config']) : [],
            'approval_config' => $data['approval_config'] ?? ['mode' => 'any'],
            'signature_config' => $data['signature_config'] ?? null,
            'action_type' => $data['action_type'] ?? 'onay',
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('success', "Adım \"{$step->name}\" güncellendi.");
    }
}
