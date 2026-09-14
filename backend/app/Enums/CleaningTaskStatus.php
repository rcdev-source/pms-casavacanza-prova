<?php

namespace App\Enums;

enum CleaningTaskStatus: string
{
    case PENDING = 'PENDING';
    case ASSIGNED = 'ASSIGNED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
}
