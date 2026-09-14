<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReservationChargeRequest;
use App\Models\Reservation;
use App\Services\ReservationChargeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReservationChargeController extends Controller
{
    public function index(Reservation $reservation): JsonResponse
    {
        Gate::authorize('view', $reservation);

        return response()->json([
            'data' => $reservation->services()->with('author')->latest('occurred_at')->get(),
        ]);
    }

    public function store(
        ReservationChargeRequest $request,
        Reservation $reservation,
        ReservationChargeService $charges,
    ): JsonResponse {
        Gate::authorize('update', $reservation);

        return response()->json([
            'data' => $charges->add($reservation, $request->validated(), $request->user()),
        ], 201);
    }
}
