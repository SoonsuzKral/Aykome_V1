<?php

namespace App\Policies;

use App\Models\License;
use App\Models\User;

class LicensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('licenses.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('licenses.manage');
    }

    public function update(User $user, License $license): bool
    {
        return $user->can('licenses.manage');
    }

    public function delete(User $user, License $license): bool
    {
        return $user->can('licenses.manage');
    }
}
