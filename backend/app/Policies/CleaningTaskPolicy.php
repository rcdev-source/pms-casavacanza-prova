<?php

namespace App\Policies;

use App\Models\CleaningTask;
use App\Models\Role;
use App\Models\User;

class CleaningTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $this->hasOperationalRole($user);
    }

    public function view(User $user, CleaningTask $task): bool
    {
        return $this->viewAny($user) && $user->belongsToProperty($task->property_id);
    }

    public function create(User $user): bool
    {
        return $user->isActive()
            && ($user->hasRole(Role::ADMIN) || $user->hasRole(Role::RECEPTION));
    }

    public function update(User $user, CleaningTask $task): bool
    {
        if (! $this->view($user, $task)) {
            return false;
        }

        return $user->hasRole(Role::ADMIN)
            || $user->hasRole(Role::RECEPTION)
            || ($user->hasRole(Role::CLEANING)
                && ($task->assigned_to === null || $task->assigned_to === $user->id));
    }

    private function hasOperationalRole(User $user): bool
    {
        return $user->hasRole(Role::ADMIN)
            || $user->hasRole(Role::RECEPTION)
            || $user->hasRole(Role::CLEANING);
    }
}
