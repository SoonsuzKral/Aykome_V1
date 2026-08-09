<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    protected function managesMunicipality(User $user): bool
    {
        return $user->isMunicipalityPersonel();
    }

    /**
     * Rol bu başvurunun şu anki adımını onaylayabiliyor mu? (Motor kontrolü)
     */
    protected function engineAllows(User $user, Application $application): bool
    {
        return app(\App\Services\ProcessEngine::class)->userCanApprove($application, $user);
    }

    public function viewAny(User $user): bool
    {
        return $user->can('applications.view');
    }

    public function view(User $user, Application $application): bool
    {
        if (! $user->can('applications.view')) {
            return false;
        }

        if ($this->managesMunicipality($user)) {
            return true;
        }

        return (int) $user->institution_id === (int) $application->institution_id;
    }

    public function create(User $user): bool
    {
        return $user->can('applications.create');
    }

    public function update(User $user, Application $application): bool
    {
        if ($user->hasRole('field-team')) {
            return false;
        }

        if (! $user->can('applications.edit')) {
            return false;
        }

        if ($this->managesMunicipality($user)) {
            return true;
        }

        return (int) $user->institution_id === (int) $application->institution_id;
    }

    public function approvePreExcavation(User $user, Application $application): bool
    {
        return $user->can('applications.approve_pre_excavation') && $this->managesMunicipality($user);
    }

    /**
     * Süreç & Onay Rotası: Belediye personeli yalnızca rolü şu anki süreç
     * adımına atanmışsa onaylayabilir. Eski sabit stage adları ('staff',
     * 'director', 'vice_mayor') yerine approval_stage artık role_key tutar;
     * yetki tamamen ProcessEngine::userCanApprove (engineAllows) ile çözülür.
     */
    public function approveStaff(User $user, Application $application): bool
    {
        return $user->can('applications.approve_pre_excavation')
            && $this->managesMunicipality($user)
            && $this->engineAllows($user, $application);
    }

    /**
     * Süreç & Onay Rotası: Müdür adımı — rolü şu anki adıma atanmışsa.
     */
    public function approveDirector(User $user, Application $application): bool
    {
        return $user->can('applications.approve_pre_excavation')
            && $this->managesMunicipality($user)
            && $this->engineAllows($user, $application);
    }

    /**
     * Süreç & Onay Rotası: Başkan Yrd. onayı — son aşama (ön kazı izni verilir).
     */
    public function approveViceMayor(User $user, Application $application): bool
    {
        return $user->can('applications.approve_pre_excavation')
            && $this->managesMunicipality($user)
            && $this->engineAllows($user, $application);
    }

    public function approvePrice(User $user, Application $application): bool
    {
        return $user->can('applications.approve_price') && $this->managesMunicipality($user);
    }

    public function approveReceipt(User $user, Application $application): bool
    {
        return $user->can('applications.approve_receipt') && $this->managesMunicipality($user);
    }

    public function delete(User $user, Application $application): bool
    {
        return $user->can('applications.delete') && $this->managesMunicipality($user);
    }

    public function transferTask(User $user, Application $application): bool
    {
        return $user->can('tasks.transfer') && $this->managesMunicipality($user);
    }

    public function transferToInstitution(User $user, Application $application): bool
    {
        // İş yönlendirme (kuruma devretme) yetkisi SADECE belediyeye aittir.
        // Alt kurum (ŞUSKİ/TEDAŞ/AKSA...) kullanıcıları bu butonu asla göremez.
        return $user->can('applications.edit') && $this->managesMunicipality($user);
    }
}
