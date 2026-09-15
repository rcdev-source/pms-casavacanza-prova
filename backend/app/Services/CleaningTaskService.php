<?php

namespace App\Services;

use App\Enums\CleaningTaskStatus;
use App\Enums\RoomStatus;
use App\Models\CheckOut;
use App\Models\CleaningTask;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CleaningTaskService
{
    public function createFromCheckOut(CheckOut $checkOut, User $user): CleaningTask
    {
        return CleaningTask::query()->firstOrCreate(
            ['check_out_id' => $checkOut->id],
            [
                'property_id' => $checkOut->property_id,
                'room_id' => $checkOut->reservation->room_id,
                'reservation_id' => $checkOut->reservation_id,
                'scheduled_for' => now(),
                'status' => CleaningTaskStatus::PENDING,
                'priority' => 4,
                'notes' => 'Pulizia automatica successiva al check-out.',
                'created_by' => $user->id,
            ],
        );
    }

    public function create(array $data, User $user): CleaningTask
    {
        $room = Room::query()->findOrFail($data['room_id']);
        $this->guardAssignee($room, $data['assigned_to'] ?? null);

        return CleaningTask::query()->create([
            ...$data,
            'property_id' => $room->property_id,
            'status' => CleaningTaskStatus::PENDING,
            'created_by' => $user->id,
        ]);
    }

    public function start(CleaningTask $task, User $user): CleaningTask
    {
        return DB::transaction(function () use ($task, $user): CleaningTask {
            $locked = CleaningTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [CleaningTaskStatus::PENDING, CleaningTaskStatus::ASSIGNED], true)) {
                throw ValidationException::withMessages(['status' => ['La pulizia non può essere avviata in questo stato.']]);
            }

            $locked->update([
                'status' => CleaningTaskStatus::IN_PROGRESS,
                'assigned_to' => $locked->assigned_to ?? $user->id,
                'started_at' => now(),
            ]);
            Room::query()->whereKey($locked->room_id)->lockForUpdate()->update(['status' => RoomStatus::CLEANING]);

            return $locked->fresh()->load('room', 'assignee');
        });
    }

    public function complete(CleaningTask $task, array $data): CleaningTask
    {
        return DB::transaction(function () use ($task, $data): CleaningTask {
            $locked = CleaningTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== CleaningTaskStatus::IN_PROGRESS) {
                throw ValidationException::withMessages(['status' => ['Avvia la pulizia prima di completarla.']]);
            }

            $locked->update([
                'status' => CleaningTaskStatus::COMPLETED,
                'completed_at' => now(),
                'completion_notes' => $data['completion_notes'] ?? null,
            ]);
            Room::query()->whereKey($locked->room_id)->lockForUpdate()->update(['status' => RoomStatus::READY]);

            return $locked->fresh()->load('room', 'assignee');
        });
    }

    private function guardAssignee(Room $room, ?string $userId): void
    {
        if ($userId === null) {
            return;
        }

        $valid = User::query()
            ->whereKey($userId)
            ->whereHas('properties', fn ($query) => $query->where('properties.id', $room->property_id))
            ->whereHas('roles', fn ($query) => $query->where('roles.key', Role::CLEANING))
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages(['assigned_to' => ['Assegna un addetto pulizie della struttura.']]);
        }
    }
}
