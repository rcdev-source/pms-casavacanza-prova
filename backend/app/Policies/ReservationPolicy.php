<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;

class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $this->canManage($user) && $user->belongsToProperty($reservation->property_id);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $this->view($user, $reservation);
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $this->view($user, $reservation);
    }

    private function canManage(User $user): bool
    {
        return $user->isActive()
            && ($user->hasRole(Role::ADMIN) || $user->hasRole(Role::RECEPTION));
    }
}
