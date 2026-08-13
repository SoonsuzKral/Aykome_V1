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
     * Kullanıcının görebildiği adımlar — personnel_ids veya roles bazlı.
     */
    public function stepsForUser(User $user): Collection
    {
        return $this->steps()->filter(function (ProcessStep $step) use ($user) {
            // Top roles see everything
            if ($user->hasAnyRole(self::TOP_ROLES)) {
                return true;
            }

            // Check if user has one of the step's roles
            $roles = $step->roles ?? [];
            if ($user->hasAnyRole($roles)) {
                return true;
            }

            // Check if user is in personnel_ids
            $personnelIds = $step->personnel_ids ?? [];
            if (in_array($user->id, $personnelIds, true)) {
                return true;
            }

            return false;
        });
    }

    /**
     * Bu adımda kullanıcının görebildiği modüller — visibility_config'e göre.
     */
    public function visibleModulesForUser(ProcessStep $step, User $user): array
    {
        $visibility = $step->visibility_config ?? [];

        // If no visibility config, fall back to approvable_modules
        if (empty($visibility)) {
            return $step->approvable_modules ?? [];
        }

        // If user is top role or has step role, they see all configured modules
        if ($user->hasAnyRole(self::TOP_ROLES) || $user->hasAnyRole($step->roles ?? [])) {
            return $visibility;
        }

        // If user is in personnel_ids, they see all configured modules
        $personnelIds = $step->personnel_ids ?? [];
        if (in_array($user->id, $personnelIds, true)) {
            return $visibility;
        }

        // Otherwise return empty
        return [];
    }

    /**
     * Kullanıcı bu adımı onaylayabilir mi? — personnel_ids + approval_config dahil.
     */
    public function canApproveStep(ProcessStep $step, User $user): bool
    {
        // Top roles can approve any step
        if ($user->hasAnyRole(self::TOP_ROLES)) {
            return true;
        }

        $approvalConfig = $step->approval_config ?? ['mode' => 'any'];

        switch ($approvalConfig['mode'] ?? 'any') {
            case 'assigned_only':
                // Only personnel_ids users can approve
                $personnelIds = $step->personnel_ids ?? [];
                return in_array($user->id, $personnelIds, true);

            case 'all':
                // User must have a role AND be in personnel_ids (if any)
                $hasRole = $user->hasAnyRole($step->roles ?? []);
                $personnelIds = $step->personnel_ids ?? [];
                $isPersonnel = in_array($user->id, $personnelIds, true);
                return $hasRole && ($isPersonnel || empty($personnelIds));

            case 'any':
            default:
                // Either has role OR is in personnel_ids
                $hasRole = $user->hasAnyRole($step->roles ?? []);
                $personnelIds = $step->personnel_ids ?? [];
                $isPersonnel = in_array($user->id, $personnelIds, true);
                return $hasRole || $isPersonnel;
        }
    }

    /**
     * Makam Masası: kullanıcının onaylayabileceği, onay bekleyen başvurular.
     */
    public function pendingForUser(User $user): Collection
    {
        // Her başvuru farklı bir sürece (process_id) bağlı olabilir.
        // Tüm aktif süreçlerdeki kullanıcının onaylayabileceği role_key'leri
        // process_id bazında topla, sonra (process_id, approval_stage) çiftiyle filtrele.
        $allProcesses = ProcessDefinition::where('is_active', true)->get();

        if ($allProcesses->isEmpty()) {
            return collect();
        }

        // [process_id => [role_key, ...]] haritası
        $processRoleKeyMap = [];
        foreach ($allProcesses as $proc) {
            $steps = $this->steps($proc);
            $roleKeys = $steps
                ->filter(fn (ProcessStep $s) => $this->roleCanApproveStep($s, $user))
                ->pluck('role_key')
                ->filter()
                ->values()
                ->all();
            if (! empty($roleKeys)) {
                $processRoleKeyMap[$proc->id] = $roleKeys;
            }
        }

        if (empty($processRoleKeyMap)) {
            return collect();
        }

        // Tüm role_key'lerin birleşimi (process_id NULL olan legacy başvurular için)
        $allRoleKeys = array_unique(array_merge(...array_values($processRoleKeyMap)));

        return Application::query()
            ->with(['institution:id,name', 'creator:id,name'])
            ->whereIn('status', ['submitted', 'pending'])
            ->whereNotNull('approval_stage')
            ->where(function ($q) use ($processRoleKeyMap, $allRoleKeys) {
                // Süreç bazlı eşleşme: her (process_id, role_key) kombinasyonu
                foreach ($processRoleKeyMap as $procId => $roleKeys) {
                    $q->orWhere(function ($q2) use ($procId, $roleKeys) {
                        $q2->where('process_id', $procId)
                            ->whereIn('approval_stage', $roleKeys);
                    });
                }
                // Legacy başvurular: process_id boş ise tüm role_key'lere bak
                $q->orWhere(function ($q2) use ($allRoleKeys) {
                    $q2->whereNull('process_id')
                        ->whereIn('approval_stage', $allRoleKeys);
                });
            })
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
            // Başkan Yrd. adımında formdan girilen ad önceliklidir (örn. "MEHMET ELĞÜN");
            // formdan ad gelmediyse (staff/director adımları) onaylayan kullanıcının adı yazılır.
            'approved_by_name' => ($name !== null && trim($name) !== '') ? trim($name) : $user->name,
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
        $labels = [
            'super-admin'          => 'Süper Admin',
            'municipality-admin'   => 'Belediye Yöneticisi',
            'municipality-staff'   => 'Belediye Personeli',
            'institution-manager'  => 'Kurum Yöneticisi',
            'institution-staff'    => 'Kurum Personeli',
            'field-team'           => 'Saha Personeli',
            'institution-admin'    => 'Kurum Yöneticisi (Üst)',
            'municipality-buro'   => 'Büro Personeli',
            'municipality-sef'    => 'Aykome Birim Şefi',
            'municipality-mudur'  => 'Fen İşleri Müdürü',
            'municipality-makam'  => 'Belediye Başkan Yardımcısı',
        ];

        $roles = Role::query()->orderBy('name')->pluck('name', 'name')->all();

        $result = [];
        foreach ($roles as $name) {
            $result[$name] = $labels[$name] ?? $name;
        }

        return $result;
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

    /**
     * Bu adımda e-imza gerekli mi?
     *
     * KRİTİK FiX: eskiden signature_config['enabled'] anahtarına bakıyordu,
     * ama gerçek veri 'require_signature' anahtarını kullanıyordu (veya hiç yoktu)
     * — bu yüzden E-İmza butonu HiÇBiR adımda görünmüyordu. Şimdi action_type
     * ('e_imza') tek kaynak — stepRequiresParaf() ile tutarlı.
     */
    public function stepRequiresSignature(ProcessStep $step): bool
    {
        return ($step->action_type ?? 'onay') === 'e_imza';
    }

    /**
     * Kullanıcı bu adımda imza atabilir mi?
     *
     * KRİTİK FiX: artık action_type='e_imza' yeterli şart; signature_config
     * ('signer_ids'/'signer_roles') SADECE ek kısıtlama olarak kullanılır —
     * boşsa role_key/roles üzerinden normal yetki kontrolüne (roleCanApproveStep)
     * düşülür, e-imza adımları artık signature_config doldurulmadığı için
     * kilitli kalmıyor.
     */
    public function canSignStep(ProcessStep $step, User $user): bool
    {
        if (($step->action_type ?? 'onay') !== 'e_imza') {
            return false;
        }

        // Super-admin her şeyi imzalayabilir
        if ($user->hasAnyRole(self::TOP_ROLES)) {
            return true;
        }

        $config = $step->signature_config ?? [];
        $signerIds = $config['signer_ids'] ?? [];
        $signerRoles = $config['signer_roles'] ?? [];

        // signer_ids/signer_roles hiç tanımlanmamışsa (çoğu adımda olduğu gibi),
        // normal adım rolü (roles/role_key) yeterli yetki kaynağıdır.
        if (empty($signerIds) && empty($signerRoles)) {
            return $this->roleCanApproveStep($step, $user);
        }

        if (! empty($signerIds) && in_array($user->id, $signerIds, true)) {
            return true;
        }

        if (! empty($signerRoles) && $user->hasAnyRole($signerRoles)) {
            return true;
        }

        return false;
    }

    /**
     * İmza config'inde belirtilen PDF tipi
     */
    public function getSignaturePdfType(ProcessStep $step): ?string
    {
        return $step->signature_config['pdf_type'] ?? null;
    }

    /**
     * Bu adımda paraf gerekli mi?
     */
    public function stepRequiresParaf(ProcessStep $step): bool
    {
        return ($step->action_type ?? 'onay') === 'paraf';
    }

    /**
     * Kullanıcı bu adımda paraf atabilir mi?
     */
    public function canParafStep(ProcessStep $step, User $user): bool
    {
        if (($step->action_type ?? 'onay') !== 'paraf') {
            return false;
        }

        // Top roles can paraffin any step
        if ($user->hasAnyRole(self::TOP_ROLES)) {
            return true;
        }

        // personnel_ids kontrolü
        $personnelIds = $step->personnel_ids ?? [];
        if (! empty($personnelIds) && in_array($user->id, $personnelIds, true)) {
            return true;
        }

        // roles kontrolü
        $roles = $step->roles ?? [];
        if (! empty($roles) && $user->hasAnyRole($roles)) {
            return true;
        }

        return false;
    }

    /**
     * Bu adımda hangi işlem tipi gerekli?
     */
    public function getStepActionType(ProcessStep $step): string
    {
        return $step->action_type ?? 'onay';
    }

    /**
     * Kullanıcı bu adımda onay/paraf/e-imza işlemi yapabilir mi?
     * (action_type'a göre ilgili yetki method'unu çağırır)
     */
    public function canPerformStepAction(ProcessStep $step, User $user): bool
    {
        $actionType = $this->getStepActionType($step);

        return match ($actionType) {
            'paraf' => $this->canParafStep($step, $user),
            'e_imza' => $this->canSignStep($step, $user),
            default => $this->canApproveStep($step, $user),
        };
    }

    // ─── Per-Module Permissions ───────────────────────────────────────────────

    /**
     * Belirli bir modül için bu adımda hangi işlem tipi gerekli?
     * (module_permissions'dan okur, yoksa step.action_type döner)
     */
    public function getModuleActionType(ProcessStep $step, string $module): string
    {
        $perms = $step->module_permissions ?? [];
        if (isset($perms[$module]['action_type'])) {
            return $perms[$module]['action_type'];
        }

        return $step->action_type ?? 'onay';
    }

    /**
     * Belirli bir modül için bu kullanıcı onay verebilir mi?
     */
    public function canApproveModule(ProcessStep $step, User $user, string $module): bool
    {
        $perms = $step->module_permissions ?? [];
        $modulePerm = $perms[$module] ?? [];

        // Fallback to step-level if no per-module config
        if (empty($modulePerm)) {
            return $this->canApproveStep($step, $user);
        }

        // Top roles can do anything
        if ($user->hasAnyRole(self::TOP_ROLES)) {
            return true;
        }

        $actionType = $modulePerm['action_type'] ?? 'onay';

        // If action type is e_imza, check signature permission
        if ($actionType === 'e_imza') {
            return $this->canSignModule($step, $user, $module);
        }

        // If action type is paraf, check paraf permission
        if ($actionType === 'paraf') {
            return $this->canParafModule($step, $user, $module);
        }

        // Check approver_roles
        $roles = $modulePerm['approver_roles'] ?? [];
        if (! empty($roles) && $user->hasAnyRole($roles)) {
            return true;
        }

        // Check approver_ids
        $ids = $modulePerm['approver_ids'] ?? [];
        if (! empty($ids) && in_array($user->id, $ids, true)) {
            return true;
        }

        // Fallback: if no specific config, use step-level personnel
        if (empty($roles) && empty($ids)) {
            return $this->canApproveStep($step, $user);
        }

        return false;
    }

    /**
     * Belirli bir modül için bu kullanıcı e-imza atabilir mi?
     */
    public function canSignModule(ProcessStep $step, User $user, string $module): bool
    {
        $perms = $step->module_permissions ?? [];
        $modulePerm = $perms[$module] ?? [];

        // Top roles can sign anything
        if ($user->hasAnyRole(self::TOP_ROLES)) {
            return true;
        }

        // Check signer_ids
        $signerIds = $modulePerm['signer_ids'] ?? [];
        if (! empty($signerIds) && in_array($user->id, $signerIds, true)) {
            return true;
        }

        // Check signer_roles
        $signerRoles = $modulePerm['signer_roles'] ?? [];
        if (! empty($signerRoles) && $user->hasAnyRole($signerRoles)) {
            return true;
        }

        return false;
    }

    /**
     * Belirli bir modül için bu kullanıcı paraf atabilir mi?
     */
    public function canParafModule(ProcessStep $step, User $user, string $module): bool
    {
        $perms = $step->module_permissions ?? [];
        $modulePerm = $perms[$module] ?? [];

        // Top roles can do anything
        if ($user->hasAnyRole(self::TOP_ROLES)) {
            return true;
        }

        // Check approver_ids (paraf uses same personnel pool)
        $ids = $modulePerm['approver_ids'] ?? [];
        if (! empty($ids) && in_array($user->id, $ids, true)) {
            return true;
        }

        // Check approver_roles
        $roles = $modulePerm['approver_roles'] ?? [];
        if (! empty($roles) && $user->hasAnyRole($roles)) {
            return true;
        }

        // Fallback to step-level
        if (empty($roles) && empty($ids)) {
            return $this->canParafStep($step, $user);
        }

        return false;
    }

    /**
     * Kullanıcı bu modülü görebilir mi? (module_permissions veya step-level)
     */
    public function canViewModule(ProcessStep $step, User $user, string $module): bool
    {
        $perms = $step->module_permissions ?? [];
        $modulePerm = $perms[$module] ?? [];

        // Top roles see everything
        if ($user->hasAnyRole(self::TOP_ROLES)) {
            return true;
        }

        // If module not in approvable_modules at all, no access
        $approvable = $step->approvable_modules ?? [];
        if (! in_array($module, $approvable, true)) {
            return false;
        }

        // Check module-specific visible_to_roles
        $visibleRoles = $modulePerm['visible_to_roles'] ?? [];
        if (! empty($visibleRoles) && $user->hasAnyRole($visibleRoles)) {
            return true;
        }

        // Check module-specific visible_to_ids
        $visibleIds = $modulePerm['visible_to_ids'] ?? [];
        if (! empty($visibleIds) && in_array($user->id, $visibleIds, true)) {
            return true;
        }

        // Fallback to step-level visibility_config
        $visibility = $step->visibility_config ?? [];
        if (empty($visibility) || in_array($module, $visibility, true)) {
            // Also check step-level personnel_ids
            $personnelIds = $step->personnel_ids ?? [];
            if (empty($personnelIds) || in_array($user->id, $personnelIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bu adımda hangi modüller bu kullanıcı tarafından görülebilir?
     */
    public function visibleModulesForUserOnStep(ProcessStep $step, User $user): array
    {
        $approvable = $step->approvable_modules ?? [];
        $visible = [];

        foreach ($approvable as $module) {
            if ($this->canViewModule($step, $user, $module)) {
                $visible[] = $module;
            }
        }

        return $visible;
    }
}
