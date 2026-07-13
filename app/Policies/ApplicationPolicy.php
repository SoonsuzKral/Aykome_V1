<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    protected function managesMunicipality(User $user): bool
    {
        return $user->hasRole(['super-admin', 'municipality-admin', 'municipality-staff']);
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
}
