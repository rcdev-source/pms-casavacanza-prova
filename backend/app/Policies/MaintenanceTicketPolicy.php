<?php

namespace App\Policies;

use App\Models\MaintenanceTicket;
use App\Models\Role;
use App\Models\User;

class MaintenanceTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $this->hasOperationalRole($user);
    }

    public function view(User $user, MaintenanceTicket $ticket): bool
    {
        return $this->viewAny($user) && $user->belongsToProperty($ticket->property_id);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $this->hasOperationalRole($user);
    }

    public function update(User $user, MaintenanceTicket $ticket): bool
    {
        if (! $this->view($user, $ticket)) {
            return false;
        }

        return $user->hasRole(Role::ADMIN)
            || $user->hasRole(Role::RECEPTION)
            || ($user->hasRole(Role::MAINTENANCE)
                && ($ticket->assigned_to === null || $ticket->assigned_to === $user->id));
    }

    private function hasOperationalRole(User $user): bool
    {
        return $user->hasRole(Role::ADMIN)
            || $user->hasRole(Role::RECEPTION)
            || $user->hasRole(Role::MAINTENANCE);
    }
}
