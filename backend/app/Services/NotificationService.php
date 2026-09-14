<?php

namespace App\Services;

use App\Jobs\CreateBroadcastNotifications;

class NotificationService
{
    public function broadcast(
        string $propertyId,
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        array $data = [],
    ): void {
        CreateBroadcastNotifications::dispatch(
            $propertyId,
            $type,
            $title,
            $message,
            $actionUrl,
            $data,
        );
    }
}
