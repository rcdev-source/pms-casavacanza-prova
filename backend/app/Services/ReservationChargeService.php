<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\ReservationService as ReservationCharge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationChargeService
{
    public function add(Reservation $reservation, array $data, User $user): ReservationCharge
    {
        return DB::transaction(function () use ($reservation, $data, $user): ReservationCharge {
            $locked = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [
                ReservationStatus::CANCELLED,
                ReservationStatus::COMPLETED,
            ], true)) {
                throw ValidationException::withMessages(['reservation' => ['Non è possibile aggiungere addebiti in questo stato.']]);
            }

            $total = bcmul((string) $data['quantity'], (string) $data['unit_price'], 2);
            $charge = ReservationCharge::query()->create([
                ...$data,
                'property_id' => $locked->property_id,
                'reservation_id' => $locked->id,
                'total' => $total,
                'occurred_at' => $data['occurred_at'] ?? now(),
                'added_by' => $user->id,
            ]);

            $this->recalculateTotals($locked);

            return $charge->load('author');
        });
    }

    public function recalculateTotals(Reservation $reservation): void
    {
        $extras = ReservationCharge::query()
            ->where('reservation_id', $reservation->id)
            ->pluck('total')
            ->reduce(fn (string $carry, $amount): string => bcadd($carry, (string) $amount, 2), '0.00');
        $total = bcadd(
            bcadd(bcsub($reservation->subtotal, $reservation->discount, 2), $reservation->taxes, 2),
            $extras,
            2,
        );
        $paid = Payment::query()
            ->where('reservation_id', $reservation->id)
            ->where('status', PaymentStatus::COMPLETED)
            ->pluck('amount')
            ->reduce(fn (string $carry, $amount): string => bcadd($carry, (string) $amount, 2), '0.00');
        $balance = bcsub($total, $paid, 2);

        $reservation->update([
            'extras_total' => $extras,
            'total' => $total,
            'balance_due' => bccomp($balance, '0.00', 2) < 0 ? '0.00' : $balance,
        ]);
    }
}
