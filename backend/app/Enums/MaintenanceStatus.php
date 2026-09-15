<?php

namespace App\Enums;

enum MaintenanceStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';
    case CLOSED = 'CLOSED';
    case CANCELLED = 'CANCELLED';
}
