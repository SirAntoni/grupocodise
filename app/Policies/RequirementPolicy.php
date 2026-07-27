<?php

namespace App\Policies;

use App\Models\Requirement;
use App\Models\User;

class RequirementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('requirements.view');
    }

    public function view(User $user, Requirement $requirement): bool
    {
        return $user->can('requirements.view');
    }

    public function create(User $user): bool
    {
        return $user->can('requirements.manage');
    }

    public function update(User $user, Requirement $requirement): bool
    {
        return $user->can('requirements.manage') && $requirement->isEditable();
    }

    public function annul(User $user, Requirement $requirement): bool
    {
        return $user->can('requirements.manage') && $requirement->isEditable();
    }
}
