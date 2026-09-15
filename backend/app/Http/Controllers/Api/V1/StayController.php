<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckInRequest;
use App\Http\Requests\Api\V1\CheckOutRequest;
use App\Models\Reservation;
use App\Services\StayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class StayController extends Controller
{
    public function checkIn(
        CheckInRequest $request,
        Reservation $reservation,
        StayService $stays,
    ): JsonResponse {
        Gate::authorize('update', $reservation);

        return response()->json([
            'data' => $stays->checkIn($reservation, $request->validated(), $request->user()),
        ], 201);
    }

    public function checkOut(
        CheckOutRequest $request,
        Reservation $reservation,
        StayService $stays,
    ): JsonResponse {
        Gate::authorize('update', $reservation);

        return response()->json([
            'data' => $stays->checkOut($reservation, $request->validated(), $request->user()),
        ], 201);
    }
}
