<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentLedgerService
{
    public function record(Reservation $reservation, array $data, User $user): Payment
    {
        return DB::transaction(function () use ($reservation, $data, $user): Payment {
            $locked = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $status = isset($data['status'])\n                ? PaymentStatus::from($data['status'])\n                : PaymentStatus::COMPLETED;

            if ($locked->status === \App\Enums\ReservationStatus::CANCELLED) {
                throw ValidationException::withMessages(['reservation' => ['Non è possibile registrare pagamenti su una prenotazione annullata.']]);
            }

            if ($status === PaymentStatus::COMPLETED
                && bccomp((string) $data['amount'], $locked->balance_due, 2) > 0) {
                throw ValidationException::withMessages(['amount' => ['L’importo supera il saldo dovuto.']]);
            }

            $payment = Payment::query()->create([
                ...$data,
                'property_id' => $locked->property_id,
                'reservation_id' => $locked->id,
                'recorded_by' => $user->id,
                'status' => $status,
                'paid_at' => $status === PaymentStatus::COMPLETED ? now() : null,
            ]);

            $this->recalculateBalance($locked);

            return $payment->load('recorder');
        });
    }

    public function refund(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PaymentStatus::COMPLETED) {
                throw ValidationException::withMessages(['payment' => ['Solo un pagamento completato può essere rimborsato.']]);
            }

            $locked->update([
                'status' => PaymentStatus::REFUNDED,
                'refunded_at' => now(),
            ]);
            $reservation = Reservation::query()->whereKey($locked->reservation_id)->lockForUpdate()->firstOrFail();
            $this->recalculateBalance($reservation);

            return $locked->fresh();
        });
    }

    public function recalculateBalance(Reservation $reservation): void
    {
        $paid = Payment::query()
            ->where('reservation_id', $reservation->id)
            ->where('status', PaymentStatus::COMPLETED)
            ->pluck('amount')
            ->reduce(fn (string $carry, $amount): string => bcadd($carry, (string) $amount, 2), '0.00');

        $balance = bcsub($reservation->total, $paid, 2);
        $reservation->update(['balance_due' => bccomp($balance, '0.00', 2) < 0 ? '0.00' : $balance]);
    }
}
