<?php

namespace App\Enums;

enum ReservationSource: string
{
    case DIRECT = 'DIRECT';
    case WEBSITE = 'WEBSITE';
    case BOOKING = 'BOOKING';
    case AIRBNB = 'AIRBNB';
    case PHONE = 'PHONE';
    case EMAIL = 'EMAIL';
    case OTHER = 'OTHER';
}
