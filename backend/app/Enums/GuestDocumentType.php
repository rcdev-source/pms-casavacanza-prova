<?php

namespace App\Enums;

enum GuestDocumentType: string
{
    case ID_CARD = 'ID_CARD';
    case PASSPORT = 'PASSPORT';
    case DRIVING_LICENSE = 'DRIVING_LICENSE';
    case OTHER = 'OTHER';
}
