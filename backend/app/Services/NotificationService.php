<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Property;

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
        $userIds = Property::query()->findOrFail($propertyId)->users()->pluck('users.id');

        foreach ($userIds as $userId) {
            Notification::query()->create([
                'property_id' => $propertyId,
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
                'data' => $data,
            ]);
        }
    }
}
