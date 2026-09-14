<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AvailabilityRequest;
use App\Models\Property;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class AvailabilityController extends Controller
{
    public function __invoke(
        AvailabilityRequest $request,
        AvailabilityService $availability,
        PricingService $pricing,
    ): JsonResponse {
        $property = Property::query()->findOrFail($request->validated('property_id'));

        if ($request->user()) {
            Gate::authorize('view', $property);
        }

        $checkIn = $request->validated('check_in_date');
        $checkOut = $request->validated('check_out_date');
        $rooms = $availability->availableRooms(
            $property,
            $checkIn,
            $checkOut,
            (int) $request->validated('adults'),
            (int) ($request->validated('children') ?? 0),
        );

        return response()->json([
            'data' => $rooms->map(fn ($room): array => [
                ...$room->toArray(),
                'pricing' => $pricing->calculate($room, $checkIn, $checkOut),
            ])->values(),
        ]);
    }
}
