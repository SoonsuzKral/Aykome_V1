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
     * Süreç & Onay Rotası: Belediye personeli yalnızca 'staff' adımındaysa
     * ve rolü bu adıma atanmışsa onaylayabilir.
     */
    public function approveStaff(User $user, Application $application): bool
    {
        if (! $user->can('applications.approve_pre_excavation') || ! $this->managesMunicipality($user)) {
            return false;
        }
        return in_array($application->approval_stage ?? 'staff', ['staff', null], true)
            && $this->engineAllows($user, $application);
    }

    /**
     * Süreç & Onay Rotası: Müdür yalnızca 'director' adımındaysa ve rolü
     * bu adıma atanmışsa onaylayabilir.
     */
    public function approveDirector(User $user, Application $application): bool
    {
        return $user->can('applications.approve_pre_excavation')
            && $this->managesMunicipality($user)
            && $application->approval_stage === 'director'
            && $this->engineAllows($user, $application);
    }

    /**
     * Süreç & Onay Rotası: Başkan Yrd. onayı — son aşama (ön kazı izni verilir).
     */
    public function approveViceMayor(User $user, Application $application): bool
    {
        return $user->can('applications.approve_pre_excavation')
            && $this->managesMunicipality($user)
            && $application->approval_stage === 'vice_mayor'
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
