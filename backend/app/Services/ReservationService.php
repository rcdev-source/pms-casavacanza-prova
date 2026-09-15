<?php

namespace App\Services;

use App\Enums\ReservationSource;
use App\Enums\ReservationStatus;
use App\Models\Guest;
use App\Models\Reservation;
use App\Models\Room;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
        private readonly ReservationStateService $states,
        private readonly ReservationChargeService $charges,
    ) {}

    public function create(array $data): Reservation
    {
        return DB::transaction(function () use ($data): Reservation {
            $room = Room::query()->with('property')->whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            $this->guardGuestsAndCapacity($room, $data);
            $this->availability->assertAvailable($room, $data['check_in_date'], $data['check_out_date']);
            $price = $this->pricing->calculate($room, $data['check_in_date'], $data['check_out_date']);
            $money = $this->money($price['subtotal'], $data);

            $reservation = Reservation::query()->create([
                ...Arr::except($data, ['guest_ids', 'discount', 'taxes', 'deposit_required', 'deposit_amount']),
                'property_id' => $room->property_id,
                'booking_code' => $this->bookingCode(),
                'status' => $data['status'] ?? ReservationStatus::CONFIRMED,
                'source' => $data['source'] ?? ReservationSource::DIRECT,
                'currency' => $room->property->currency,
                ...$money,
            ]);

            $this->syncGuests($reservation, $data);

            return $reservation->load('room', 'primaryGuest', 'guests');
        }, 3);
    }

    public function update(Reservation $reservation, array $data): Reservation
    {
        return DB::transaction(function () use ($reservation, $data): Reservation {
            if (! in_array($reservation->status, [
                ReservationStatus::DRAFT,
                ReservationStatus::REQUESTED,
                ReservationStatus::CONFIRMED,
                ReservationStatus::PRE_CHECKIN,
            ], true)) {
                throw ValidationException::withMessages(['status' => ['La prenotazione non è più modificabile.']]);
            }

            $room = Room::query()->with('property')->whereKey($data['room_id'])->lockForUpdate()->firstOrFail();
            $this->guardGuestsAndCapacity($room, $data);
            $this->availability->assertAvailable(
                $room,
                $data['check_in_date'],
                $data['check_out_date'],
                $reservation->id,
            );
            $price = $this->pricing->calculate($room, $data['check_in_date'], $data['check_out_date']);
            $money = $this->money($price['subtotal'], $data);

            $reservation->update([
                ...Arr::except($data, ['guest_ids', 'discount', 'taxes', 'deposit_required', 'deposit_amount']),
                'property_id' => $room->property_id,
                'currency' => $room->property->currency,
                ...$money,
            ]);
            $this->syncGuests($reservation, $data);

            $this->charges->recalculateTotals($reservation);

            return $reservation->fresh()->load('room', 'primaryGuest', 'guests');
        }, 3);
    }

    public function cancel(Reservation $reservation, ?string $reason): Reservation
    {
        return DB::transaction(function () use ($reservation, $reason): Reservation {
            $locked = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $this->states->transition($locked, ReservationStatus::CANCELLED);
            $locked->cancelled_at = now();
            $locked->cancellation_reason = $reason;
            $locked->save();

            return $locked;
        });
    }

    private function guardGuestsAndCapacity(Room $room, array $data): void
    {
        if ($data['adults'] > $room->max_adults
            || $data['children'] > $room->max_children
            || ($data['adults'] + $data['children']) > $room->max_guests) {
            throw ValidationException::withMessages(['adults' => ['Il numero di ospiti supera la capienza della camera.']]);
        }

        $guestIds = array_unique([$data['primary_guest_id'], ...($data['guest_ids'] ?? [])]);
        $guestCount = Guest::query()
            ->whereIn('id', $guestIds)
            ->whereHas('properties', fn ($query) => $query->where('properties.id', $room->property_id))
            ->count();

        if ($guestCount !== count($guestIds)) {
            throw ValidationException::withMessages(['guest_ids' => ['Uno o più ospiti non appartengono alla struttura.']]);
        }
    }

    private function money(string $subtotal, array $data): array
    {
        $discount = (string) ($data['discount'] ?? '0.00');
        $taxes = (string) ($data['taxes'] ?? '0.00');
        $total = bcadd(bcsub($subtotal, $discount, 2), $taxes, 2);
        $depositRequired = (bool) ($data['deposit_required'] ?? false);
        $depositAmount = (string) ($data['deposit_amount'] ?? '0.00');

        if (bccomp($total, '0.00', 2) < 0 || bccomp($depositAmount, $total, 2) > 0) {
            throw ValidationException::withMessages(['total' => ['Gli importi della prenotazione non sono validi.']]);
        }

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'taxes' => $taxes,
            'extras_total' => '0.00',
            'total' => $total,
            'deposit_required' => $depositRequired,
            'deposit_amount' => $depositAmount,
            'balance_due' => $total,
        ];
    }

    private function syncGuests(Reservation $reservation, array $data): void
    {
        $ids = array_unique([$data['primary_guest_id'], ...($data['guest_ids'] ?? [])]);
        $sync = [];

        foreach ($ids as $id) {
            $sync[$id] = ['is_primary' => $id === $data['primary_guest_id']];
        }

        $reservation->guests()->sync($sync);
    }

    private function bookingCode(): string
    {
        do {
            $code = 'CV-'.Str::upper(Str::random(10));
        } while (Reservation::query()->where('booking_code', $code)->exists());

        return $code;
    }
}
