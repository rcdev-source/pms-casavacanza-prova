<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Exceptions\RoomNotAvailableException;
use App\Models\Property;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function assertAvailable(
        Room $room,
        string $checkIn,
        string $checkOut,
        ?string $ignoreReservationId = null,
    ): void {
        if (! $room->is_active || in_array($room->status, [RoomStatus::MAINTENANCE, RoomStatus::BLOCKED], true)) {
            throw new RoomNotAvailableException;
        }

        $reservationConflict = $room->reservations()
            ->whereIn('status', ReservationStatus::blockingValues())
            ->when(
                $ignoreReservationId,
                fn (Builder $query, string $id): Builder => $query->where('reservations.id', '!=', $id),
            )
            ->where('check_in_date', '<', $checkOut)
            ->where('check_out_date', '>', $checkIn)
            ->exists();

        $blockConflict = $room->availabilityBlocks()
            ->where('start_date', '<', $checkOut)
            ->where('end_date', '>', $checkIn)
            ->exists();

        if ($reservationConflict || $blockConflict) {
            throw new RoomNotAvailableException;
        }
    }

    public function availableRooms(
        Property $property,
        string $checkIn,
        string $checkOut,
        int $adults,
        int $children,
    ): Collection {
        return $property->rooms()
            ->with('amenities')
            ->where('is_active', true)
            ->whereNotIn('status', [RoomStatus::MAINTENANCE->value, RoomStatus::BLOCKED->value])
            ->where('max_adults', '>=', $adults)
            ->where('max_children', '>=', $children)
            ->where('max_guests', '>=', $adults + $children)
            ->whereDoesntHave('reservations', function (Builder $query) use ($checkIn, $checkOut): void {
                $query->whereIn('status', ReservationStatus::blockingValues())
                    ->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            })
            ->whereDoesntHave('availabilityBlocks', function (Builder $query) use ($checkIn, $checkOut): void {
                $query->where('start_date', '<', $checkOut)
                    ->where('end_date', '>', $checkIn);
            })
            ->orderBy('name')
            ->get();
    }

    public function nights(string $checkIn, string $checkOut): int
    {
        return (int) CarbonImmutable::parse($checkIn)->diffInDays(CarbonImmutable::parse($checkOut));
    }
}
