<?php

namespace App\Services;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\ApplicationAudit;
use App\Models\ProcessDefinition;
use App\Models\ProcessStep;
use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Süreç & Onay Rotası Motoru (Hiyerarşi Yönetim Modülü)
 * -----------------------------------------------------
 * "Sabit işleyiş" yoktur; tüm onay silsilesi process_definitions +
 * process_steps tablolarından okunur. Belediye merkez yönetimi bu rotayı
 * tanımlar, alt kurumlar asla erişemez. Başvurular bu rotaya göre adım
 * adım ilerler ve rolü rotada olmayan kullanıcı onay tuşunu GÖREMEZ.
 *
 * Legacy uyumluluk: default süreçteki role_key değerleri (staff/director/
 * vice_mayor) mevcut staff_approved_* / director_approved_* /
 * vice_mayor_approved_* kolonlarını da eşzamanlı doldurur.
 */
class ProcessEngine
{
    /**
     * En tepedeki "tam yetkili" makamlar — tüm adımlara karışabilir.
     * (Başkan ve Yöneticiler kuralımızda en yetkilidir.)
     */
    public const TOP_ROLES = ['super-admin', 'municipality-admin', 'municipality-makam'];

    /**
     * Legacy onay kolonları — role_key → applications kolonları eşlemesi.
     */
    public const LEGACY_FIELDS = [
        'staff' => ['by' => 'staff_approved_by', 'at' => 'staff_approved_at'],
        'director' => ['by' => 'director_approved_by', 'at' => 'director_approved_at'],
        'vice_mayor' => ['by' => 'vice_mayor_approved_by', 'at' => 'vice_mayor_approved_at'],
    ];

    public function activeProcess(): ?ProcessDefinition
    {
        return ProcessDefinition::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }

    /**
     * Başvurunun bağlı olduğu süreç. Başvuruda process_id seçiliyse o süreç,
     * değilse aktif (varsayılan) süreç kullanılır.
     */
    public function processFor(Application $application): ?ProcessDefinition
    {
        if ($application->process_id) {
            $process = ProcessDefinition::find($application->process_id);
            if ($process) {
                return $process;
            }
        }

        return $this->activeProcess();
    }

    public function steps(?ProcessDefinition $process = null, ?Application $application = null): Collection
    {
        if ($process === null && $application !== null) {
            $process = $this->processFor($application);
        }
        $process = $process ?? $this->activeProcess();
        if (! $process) {
            return collect();
        }

        return $process->steps()->where('is_active', true)->get();
    }

    public function firstStep(): ?ProcessStep
    {
        return $this->steps()->first();
    }

    public function stepForRoleKey(string $roleKey): ?ProcessStep
    {
        return $this->steps()->firstWhere('role_key', $roleKey);
    }

    /**
     * Başvurunun şu an bulunduğu adım. approval_stage boşsa ilk adım.
     */
    public function currentStep(Application $application): ?ProcessStep
    {
        $steps = $this->steps(null, $application);
        if ($steps->isEmpty()) {
            return null;
        }

        $stage = $application->approval_stage;
        if ($stage && $stage !== 'approved') {
            $step = $steps->firstWhere('role_key', $stage);
            if ($step) {
                return $step;
            }
        }

        return $steps->first();
    }

    public function nextStep(Application $application): ?ProcessStep
    {
        $steps = $this->steps(null, $application);
        $current = $this->currentStep($application);
        if (! $current) {
            return null;
        }

        $index = $steps->search(fn (ProcessStep $s) => $s->id === $current->id);
        if ($index === false) {
            return null;
        }

        return $steps->get($index + 1);
    }

    public function isLastStep(Application $application): bool
    {
        return $this->nextStep($application) === null;
    }

    /**
     * Rol bu adımı onaylayabilir mi? En tepedeki makamlar her adımı
     * onaylayabilir (hiyerarşi), diğer rollerse yalnızca adımlarındaysa.
     */
    public function roleCanApproveStep(ProcessStep $step, User $user): bool
    {
        if ($user->hasAnyRole(self::TOP_ROLES)) {
            return true;
        }

        $roles = $step->roles ?? [];

        return $user->hasAnyRole($roles);
    }

    /**
     * Kullanıcının rolü aktif süreçte herhangi bir adıma atanmış mı?
     */
    public function hasAnyStepRole(User $user): bool
    {
        return $this->steps()->contains(fn (ProcessStep $s) => $this->roleCanApproveStep($s, $user));
    }

    /**
     * Kullanıcı bu başvuruyu (şu anki adımında) onaylayabilir mi?
     */
    public function userCanApprove(Application $application, User $user): bool
    {
        if (($application->approval_stage ?? '') === 'approved') {
            return false;
        }

        $step = $this->currentStep($application);

        return $step !== null && $this->roleCanApproveStep($step, $user);
    }

    /**
     * Kullanıcının rolü belirtilen modüle "karışabilir" mi (görüntüleyebilir/onaylayabilir)?
     */
    public function userCanAccessModule(User $user, string $module): bool
    {
        return $this->steps()->contains(function (ProcessStep $step) use ($user, $module) {
            return $this->roleCanApproveStep($step, $user)
                && in_array($module, $step->approvable_modules ?? [], true);
        });
    }

    /**
     * Makam Masası: kullanıcının onaylayabileceği, onay bekleyen başvurular.
     */
    public function pendingForUser(User $user): Collection
    {
        $steps = $this->steps();
        if ($steps->isEmpty()) {
            return collect();
        }

        $roleKeys = $steps
            ->filter(fn (ProcessStep $s) => $this->roleCanApproveStep($s, $user))
            ->pluck('role_key')
            ->all();

        return Application::query()
            ->with(['institution:id,name', 'creator:id,name'])
            ->whereIn('status', ['submitted', 'pending'])
            ->whereNotNull('approval_stage')
            ->whereIn('approval_stage', $roleKeys)
            ->latest()
            ->get();
    }

    /**
     * Onayı ilerletir. Adım kaydını approval_log'a yazar, legacy kolonları
     * doldurur, sıradaki adıma geçirir. Son adım ise Ön Kazı İzni verir.
     *
     * @return array{approved: bool, finished: bool, stage: ?string, next: ?ProcessStep, reason: ?string}
     */
    public function approve(Application $application, User $user, ?string $name = null): array
    {
        $steps = $this->steps(null, $application);
        if ($steps->isEmpty()) {
            return ['approved' => false, 'finished' => false, 'stage' => null, 'next' => null, 'reason' => 'no_process'];
        }

        $current = $this->currentStep($application);
        if (! $current) {
            return ['approved' => false, 'finished' => false, 'stage' => null, 'next' => null, 'reason' => 'no_step'];
        }

        if (! $this->roleCanApproveStep($current, $user)) {
            return ['approved' => false, 'finished' => false, 'stage' => $current->role_key, 'next' => null, 'reason' => 'not_authorized'];
        }

        $oldStatus = $application->status instanceof \BackedEnum ? $application->status->value : $application->status;
        $now = now();
        $updates = [];

        $legacy = self::LEGACY_FIELDS[$current->role_key] ?? null;
        if ($legacy) {
            $updates[$legacy['by']] = $user->id;
            $updates[$legacy['at']] = $now;
        }

        $log = $application->approval_log ?? [];
        $log[] = [
            'step_id' => $current->id,
            'step_name' => $current->name,
            'role_key' => $current->role_key,
            'module' => $current->approvable_modules[0] ?? 'pre_excavation',
            'approved_by' => $user->id,
            'approved_by_name' => $user->name,
            'approved_at' => $now->toDateTimeString(),
        ];
        $updates['approval_log'] = $log;

        $index = $steps->search(fn (ProcessStep $s) => $s->id === $current->id);
        $next = $steps->get($index + 1);

        if ($next) {
            $updates['approval_stage'] = $next->role_key;
            $application->update($updates);

            return ['approved' => true, 'finished' => false, 'stage' => $current->role_key, 'next' => $next, 'reason' => null];
        }

        // Son adım — Ön Kazı İzni verilir.
        $updates['approval_stage'] = 'approved';
        $updates['status'] = ApplicationStatus::PreApproved;
        $updates['pre_excavation_approved_at'] = $now;
        $updates['pre_excavation_approved_by'] = $user->id;
        $updates['vice_mayor_approved_by'] = $user->id;
        $updates['vice_mayor_approved_at'] = $now;
        if (! empty($name)) {
            $updates['vice_mayor_name'] = $name;
        }

        $application->update($updates);

        ApplicationAudit::create([
            'application_id' => $application->id,
            'user_id' => $user->id,
            'action' => 'Ön Kazı Onayı Verildi',
            'old_status' => $oldStatus,
            'new_status' => ApplicationStatus::PreApproved->value,
        ]);

        return ['approved' => true, 'finished' => true, 'stage' => $current->role_key, 'next' => null, 'reason' => null];
    }

    public function moduleOptions(): array
    {
        return [
            'pre_excavation' => 'Ön Kazı Onayı',
            'metraj' => 'Metraj Onayı',
            'ruhsat' => 'Ruhsat İzni',
            'tahakkuk' => 'Tahakkuk',
            'makbuz' => 'Tahsilat Makbuzu',
            'taahhutname' => 'Taahhütname',
        ];
    }

    public function roleOptions(): array
    {
        return Role::query()->orderBy('name')->pluck('name', 'name')->all();
    }

    public function stageLabel(?string $stage): string
    {
        $step = $stage ? $this->steps()->firstWhere('role_key', $stage) : null;

        return $step?->name ?? (match ($stage) {
            'staff' => 'Büro Personeli Onayı',
            'director' => 'Müdür Onayı',
            'vice_mayor' => 'Başkan Yrd. Onayı',
            'approved' => 'Onaylandı',
            default => 'Beklemede',
        });
    }
}
