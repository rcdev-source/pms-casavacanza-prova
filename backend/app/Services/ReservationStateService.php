<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use DomainException;

class ReservationStateService
{
    private const TRANSITIONS = [
        'DRAFT' => ['REQUESTED', 'CONFIRMED', 'CANCELLED'],
        'REQUESTED' => ['CONFIRMED', 'CANCELLED'],
        'CONFIRMED' => ['PRE_CHECKIN', 'CHECKED_IN', 'CANCELLED', 'NO_SHOW'],
        'PRE_CHECKIN' => ['CHECKED_IN', 'CANCELLED', 'NO_SHOW'],
        'CHECKED_IN' => ['IN_HOUSE'],
        'IN_HOUSE' => ['CHECKED_OUT'],
        'CHECKED_OUT' => ['COMPLETED'],
        'COMPLETED' => [],
        'CANCELLED' => [],
        'NO_SHOW' => [],
    ];

    public function transition(Reservation $reservation, ReservationStatus $target): void
    {
        $allowed = self::TRANSITIONS[$reservation->status->value] ?? [];

        if (! in_array($target->value, $allowed, true)) {
            throw new DomainException("Transizione non valida: {$reservation->status->value} → {$target->value}");
        }

        $reservation->status = $target;
    }
}
