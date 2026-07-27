<?php

namespace App\Policies;

use App\Models\DispatchGuide;
use App\Models\User;

class DispatchGuidePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('guides.view');
    }

    public function view(User $user, DispatchGuide $guide): bool
    {
        return $user->can('guides.view');
    }

    public function create(User $user): bool
    {
        return $user->can('guides.manage');
    }

    public function update(User $user, DispatchGuide $guide): bool
    {
        return $user->can('guides.manage') && $guide->isDraft();
    }

    public function issue(User $user, DispatchGuide $guide): bool
    {
        return $user->can('guides.manage') && $guide->isDraft();
    }

    public function duplicate(User $user, DispatchGuide $guide): bool
    {
        return $user->can('guides.manage');
    }

    public function annul(User $user, DispatchGuide $guide): bool
    {
        return $user->can('guides.manage') && $guide->isIssued();
    }

    public function resend(User $user, DispatchGuide $guide): bool
    {
        return $user->can('guides.manage')
            && $guide->isIssued()
            && $guide->electronicDocument !== null
            && ! $guide->electronicDocument->sunat_status->isAccepted();
    }

    public function delete(User $user, DispatchGuide $guide): bool
    {
        return $user->can('guides.manage') && $guide->isDraft();
    }
}
