<?php

namespace App\Policies;

use App\Models\Guest;
use App\Models\Role;
use App\Models\User;

class GuestPolicy
{
    public function view(User $user, Guest $guest): bool
    {
        return $user->isActive()
            && $guest->properties()->whereIn('properties.id', $user->properties()->select('properties.id'))->exists()
            && ($user->hasRole(Role::ADMIN) || $user->hasRole(Role::RECEPTION));
    }

    public function create(User $user): bool
    {
        return $user->isActive()
            && ($user->hasRole(Role::ADMIN) || $user->hasRole(Role::RECEPTION));
    }

    public function update(User $user, Guest $guest): bool
    {
        return $this->view($user, $guest);
    }
}
