<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CancelReservationRequest;
use App\Http\Requests\Api\V1\ReservationRequest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ReservationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Reservation::class);
        $validated = $request->validate([
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after:from'],
            'status' => ['nullable', Rule::enum(ReservationStatus::class)],
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        $reservations = Reservation::query()
            ->with('room', 'primaryGuest', 'guests')
            ->where('property_id', $property->id)
            ->when($validated['from'] ?? null, fn ($query, $from) => $query->where('check_out_date', '>', $from))
            ->when($validated['to'] ?? null, fn ($query, $to) => $query->where('check_in_date', '<', $to))
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->orderBy('check_in_date')
            ->get();

        return response()->json(['data' => $reservations]);
    }

    public function store(ReservationRequest $request, ReservationService $service): JsonResponse
    {
        Gate::authorize('create', Reservation::class);
        $room = Room::query()->with('property')->findOrFail($request->validated('room_id'));
        Gate::authorize('view', $room->property);

        return response()->json(['data' => $service->create($request->validated())], 201);
    }

    public function show(Reservation $reservation): JsonResponse
    {
        Gate::authorize('view', $reservation);

        return response()->json([
            'data' => $reservation->load('property', 'room.amenities', 'primaryGuest', 'guests'),
        ]);
    }

    public function update(
        ReservationRequest $request,
        Reservation $reservation,
        ReservationService $service,
    ): JsonResponse {
        Gate::authorize('update', $reservation);
        $room = Room::query()->with('property')->findOrFail($request->validated('room_id'));
        Gate::authorize('view', $room->property);

        return response()->json(['data' => $service->update($reservation, $request->validated())]);
    }

    public function destroy(
        CancelReservationRequest $request,
        Reservation $reservation,
        ReservationService $service,
    ): JsonResponse {
        Gate::authorize('delete', $reservation);

        return response()->json([
            'data' => $service->cancel($reservation, $request->validated('reason')),
        ]);
    }
}
