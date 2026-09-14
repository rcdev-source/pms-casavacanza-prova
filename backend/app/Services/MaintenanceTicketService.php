<?php

namespace App\Services;

use App\Enums\MaintenanceStatus;
use App\Enums\RoomStatus;
use App\Models\AvailabilityBlock;
use App\Models\MaintenanceTicket;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MaintenanceTicketService
{
    public function create(array $data, User $user): MaintenanceTicket
    {
        return DB::transaction(function () use ($data, $user): MaintenanceTicket {
            $room = Room::query()->whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            $this->guardAssignee($room, $data['assigned_to'] ?? null);
            $block = null;

            if ($data['blocks_room']) {
                $block = AvailabilityBlock::query()->create([
                    'property_id' => $room->property_id,
                    'room_id' => $room->id,
                    'start_date' => now()->toDateString(),
                    'end_date' => '2099-12-31',
                    'reason' => 'Manutenzione: '.$data['title'],
                    'created_by' => $user->id,
                ]);
                $room->update(['status' => RoomStatus::MAINTENANCE]);
            }

            return MaintenanceTicket::query()->create([
                ...$data,
                'property_id' => $room->property_id,
                'status' => MaintenanceStatus::OPEN,
                'availability_block_id' => $block?->id,
                'reported_by' => $user->id,
            ])->load('room', 'assignee', 'reporter');
        });
    }

    public function start(MaintenanceTicket $ticket, User $user): MaintenanceTicket
    {
        if ($ticket->status !== MaintenanceStatus::OPEN) {
            throw ValidationException::withMessages(['status' => ['Il ticket non può essere avviato.']]);
        }

        $ticket->update([
            'status' => MaintenanceStatus::IN_PROGRESS,
            'assigned_to' => $ticket->assigned_to ?? $user->id,
            'started_at' => now(),
        ]);

        return $ticket->fresh()->load('room', 'assignee');
    }

    public function resolve(MaintenanceTicket $ticket, array $data): MaintenanceTicket
    {
        return DB::transaction(function () use ($ticket, $data): MaintenanceTicket {
            $locked = MaintenanceTicket::query()->whereKey($ticket->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [MaintenanceStatus::OPEN, MaintenanceStatus::IN_PROGRESS], true)) {
                throw ValidationException::withMessages(['status' => ['Il ticket non può essere risolto.']]);
            }

            $locked->update([
                'status' => MaintenanceStatus::RESOLVED,
                'resolved_at' => now(),
                'resolution_notes' => $data['resolution_notes'],
            ]);
            $locked->availabilityBlock?->delete();

            $hasOtherBlockingTickets = MaintenanceTicket::query()
                ->where('room_id', $locked->room_id)
                ->where('id', '!=', $locked->id)
                ->where('blocks_room', true)
                ->whereIn('status', [MaintenanceStatus::OPEN, MaintenanceStatus::IN_PROGRESS])
                ->exists();

            if (! $hasOtherBlockingTickets) {
                Room::query()->whereKey($locked->room_id)->lockForUpdate()->update(['status' => RoomStatus::READY]);
            }

            return $locked->fresh()->load('room', 'assignee');
        });
    }

    public function close(MaintenanceTicket $ticket): MaintenanceTicket
    {
        if ($ticket->status !== MaintenanceStatus::RESOLVED) {
            throw ValidationException::withMessages(['status' => ['Risolvi il ticket prima di chiuderlo.']]);
        }

        $ticket->update(['status' => MaintenanceStatus::CLOSED, 'closed_at' => now()]);

        return $ticket->fresh();
    }

    private function guardAssignee(Room $room, ?string $userId): void
    {
        if ($userId === null) {
            return;
        }

        $valid = User::query()
            ->whereKey($userId)
            ->whereHas('properties', fn ($query) => $query->where('properties.id', $room->property_id))
            ->whereHas('roles', fn ($query) => $query->where('roles.key', Role::MAINTENANCE))
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages(['assigned_to' => ['Assegna un manutentore della struttura.']]);
        }
    }
}
