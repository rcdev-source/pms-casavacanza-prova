<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function view(User $user, Room $room): bool
    {
        return $user->isActive() && $user->belongsToProperty($room->property_id);
    }

    public function create(User $user): bool
    {
        return $user->isActive() && $user->hasRole(Role::ADMIN);
    }

    public function update(User $user, Room $room): bool
    {
        return $this->view($user, $room) && $user->hasRole(Role::ADMIN);
    }
}
