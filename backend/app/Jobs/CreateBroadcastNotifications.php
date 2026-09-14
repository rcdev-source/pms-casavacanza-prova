<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Property;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CreateBroadcastNotifications implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public readonly string $propertyId,
        public readonly string $type,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $actionUrl,
        public readonly array $data,
    ) {}

    public function handle(): void
    {
        $userIds = Property::query()->findOrFail($this->propertyId)->users()->pluck('users.id');

        foreach ($userIds as $userId) {
            Notification::query()->create([
                'property_id' => $this->propertyId,
                'user_id' => $userId,
                'type' => $this->type,
                'title' => $this->title,
                'message' => $this->message,
                'action_url' => $this->actionUrl,
                'data' => $this->data,
            ]);
        }
    }

    public function backoff(): array
    {
        return [5, 30, 120];
    }
}
