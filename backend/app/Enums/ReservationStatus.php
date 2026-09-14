<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case DRAFT = 'DRAFT';
    case REQUESTED = 'REQUESTED';
    case CONFIRMED = 'CONFIRMED';
    case PRE_CHECKIN = 'PRE_CHECKIN';
    case CHECKED_IN = 'CHECKED_IN';
    case IN_HOUSE = 'IN_HOUSE';
    case CHECKED_OUT = 'CHECKED_OUT';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
    case NO_SHOW = 'NO_SHOW';

    public static function blockingValues(): array
    {
        return [
            self::REQUESTED->value,
            self::CONFIRMED->value,
            self::PRE_CHECKIN->value,
            self::CHECKED_IN->value,
            self::IN_HOUSE->value,
            self::CHECKED_OUT->value,
        ];
    }
}
