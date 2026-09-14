<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\Role;
use App\Models\User;

class PropertyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    public function view(User $user, Property $property): bool
    {
        return $user->isActive() && $user->belongsToProperty($property);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasRole(Role::ADMIN);
    }

    public function update(User $user, Property $property): bool
    {
        return $this->view($user, $property) && $user->hasRole(Role::ADMIN);
    }
}
