<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Models\CheckIn;
use App\Models\CheckOut;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StayService
{
    public function __construct(
        private readonly ReservationStateService $states,
        private readonly ReservationChargeService $charges,
    ) {}

    public function checkIn(Reservation $reservation, array $data, User $user): CheckIn
    {
        return DB::transaction(function () use ($reservation, $data, $user): CheckIn {
            $locked = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (CheckIn::query()->where('reservation_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['reservation' => ['Il check-in è già stato completato.']]);
            }

            if (! in_array($locked->status, [ReservationStatus::CONFIRMED, ReservationStatus::PRE_CHECKIN], true)) {
                throw ValidationException::withMessages(['status' => ['La prenotazione non è pronta per il check-in.']]);
            }

            $this->states->transition($locked, ReservationStatus::CHECKED_IN);
            $locked->save();
            Room::query()->whereKey($locked->room_id)->lockForUpdate()->update(['status' => RoomStatus::OCCUPIED]);

            return CheckIn::query()->create([
                ...$data,
                'property_id' => $locked->property_id,
                'reservation_id' => $locked->id,
                'completed_at' => now(),
                'completed_by' => $user->id,
            ]);
        });
    }

    public function checkOut(Reservation $reservation, array $data, User $user): CheckOut
    {
        return DB::transaction(function () use ($reservation, $data, $user): CheckOut {
            $locked = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (CheckOut::query()->where('reservation_id', $locked->id)->exists()) {
                throw ValidationException::withMessages(['reservation' => ['Il check-out è già stato completato.']]);
            }

            if (! in_array($locked->status, [ReservationStatus::CHECKED_IN, ReservationStatus::IN_HOUSE], true)) {
                throw ValidationException::withMessages(['status' => ['La prenotazione non è pronta per il check-out.']]);
            }

            if (bccomp((string) ($data['damages_amount'] ?? '0.00'), '0.00', 2) > 0) {
                $this->charges->add($locked, [
                    'description' => 'Danni rilevati al check-out',
                    'quantity' => '1.00',
                    'unit_price' => (string) $data['damages_amount'],
                    'occurred_at' => now(),
                ], $user);
                $locked->refresh();
            }

            if ($locked->status === ReservationStatus::CHECKED_IN) {
                $this->states->transition($locked, ReservationStatus::IN_HOUSE);
            }
            $this->states->transition($locked, ReservationStatus::CHECKED_OUT);
            $locked->save();
            Room::query()->whereKey($locked->room_id)->lockForUpdate()->update(['status' => RoomStatus::DIRTY]);

            return CheckOut::query()->create([
                ...$data,
                'property_id' => $locked->property_id,
                'reservation_id' => $locked->id,
                'completed_at' => now(),
                'completed_by' => $user->id,
            ]);
        });
    }
}
