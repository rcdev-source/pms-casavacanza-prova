<?php

namespace App\Enums;

enum RoomStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case RESERVED = 'RESERVED';
    case OCCUPIED = 'OCCUPIED';
    case DIRTY = 'DIRTY';
    case CLEANING = 'CLEANING';
    case READY = 'READY';
    case MAINTENANCE = 'MAINTENANCE';
    case BLOCKED = 'BLOCKED';
}
