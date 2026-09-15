<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\PreCheckInToken;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PreCheckInService
{
    public function __construct(private readonly ReservationStateService $states) {}

    public function issue(Reservation $reservation, User $user, int $hours): array
    {
        if (! in_array($reservation->status, [ReservationStatus::CONFIRMED, ReservationStatus::PRE_CHECKIN], true)) {
            throw ValidationException::withMessages(['status' => ['Il pre-check-in richiede una prenotazione confermata.']]);
        }

        $rawToken = bin2hex(random_bytes(32));
        $reservation->preCheckInTokens()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        $token = $reservation->preCheckInTokens()->create([
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addHours($hours),
            'created_by' => $user->id,
        ]);

        return [
            'expires_at' => $token->expires_at,
            'url' => rtrim((string) config('app.frontend_url'), '/').'/pre-check-in/'.$rawToken,
        ];
    }

    public function findValid(string $rawToken): PreCheckInToken
    {
        return PreCheckInToken::query()
            ->with('reservation.room', 'reservation.primaryGuest')
            ->where('token_hash', hash('sha256', $rawToken))
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();
    }

    public function complete(string $rawToken, array $data): Reservation
    {
        return DB::transaction(function () use ($rawToken, $data): Reservation {
            $token = PreCheckInToken::query()
                ->where('token_hash', hash('sha256', $rawToken))
                ->lockForUpdate()
                ->firstOrFail();

            if ($token->used_at !== null || $token->expires_at->isPast()) {
                throw ValidationException::withMessages(['token' => ['Il link non è più valido.']]);
            }

            $reservation = Reservation::query()->whereKey($token->reservation_id)->lockForUpdate()->firstOrFail();
            $reservation->primaryGuest->update(Arr::only($data, [
                'first_name',
                'last_name',
                'birth_date',
                'birth_place',
                'nationality',
                'email',
                'phone',
                'address',
                'city',
                'postal_code',
                'country',
            ]));
            $token->update([
                'payload' => Arr::only($data, [
                    'document_type',
                    'document_number',
                    'document_issuing_country',
                    'document_expiry_date',
                ]),
                'used_at' => now(),
            ]);

            if ($reservation->status === ReservationStatus::CONFIRMED) {
                $this->states->transition($reservation, ReservationStatus::PRE_CHECKIN);
                $reservation->save();
            }

            return $reservation->fresh()->load('room', 'primaryGuest');
        });
    }
}
