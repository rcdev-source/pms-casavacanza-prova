<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IssuePreCheckInRequest;
use App\Http\Requests\Api\V1\PublicPreCheckInRequest;
use App\Models\Reservation;
use App\Services\PreCheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PreCheckInController extends Controller
{
    public function issue(
        IssuePreCheckInRequest $request,
        Reservation $reservation,
        PreCheckInService $preCheckIn,
    ): JsonResponse {
        Gate::authorize('update', $reservation);

        return response()->json([
            'data' => $preCheckIn->issue(
                $reservation,
                $request->user(),
                (int) ($request->validated('expires_in_hours') ?? 72),
            ),
        ], 201);
    }

    public function show(string $token, PreCheckInService $preCheckIn): JsonResponse
    {
        $record = $preCheckIn->findValid($token);
        $reservation = $record->reservation;

        return response()->json([
            'data' => [
                'booking_code' => $reservation->booking_code,
                'check_in_date' => $reservation->check_in_date->toDateString(),
                'check_out_date' => $reservation->check_out_date->toDateString(),
                'room_name' => $reservation->room->name,
                'expires_at' => $record->expires_at,
                'guest' => $reservation->primaryGuest->only([
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
                ]),
            ],
        ]);
    }

    public function complete(
        PublicPreCheckInRequest $request,
        string $token,
        PreCheckInService $preCheckIn,
    ): JsonResponse {
        $reservation = $preCheckIn->complete($token, $request->validated());

        return response()->json([
            'data' => [
                'booking_code' => $reservation->booking_code,
                'status' => $reservation->status,
                'message' => 'Pre-check-in completato.',
            ],
        ]);
    }
}
