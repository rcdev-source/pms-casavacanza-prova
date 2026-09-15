<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AvailabilityBlockRequest;
use App\Models\AvailabilityBlock;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AvailabilityBlockController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['property_id' => ['required', 'ulid', 'exists:properties,id']]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        return response()->json([
            'data' => AvailabilityBlock::query()
                ->with('room')
                ->where('property_id', $property->id)
                ->orderBy('start_date')
                ->get(),
        ]);
    }

    public function store(AvailabilityBlockRequest $request): JsonResponse
    {
        $room = Room::query()->with('property')->findOrFail($request->validated('room_id'));
        Gate::authorize('update', $room->property);
        $block = AvailabilityBlock::query()->create([
            ...$request->validated(),
            'property_id' => $room->property_id,
            'created_by' => $request->user()->id,
        ]);

        return response()->json(['data' => $block->load('room')], 201);
    }

    public function destroy(AvailabilityBlock $availabilityBlock): JsonResponse
    {
        Gate::authorize('update', $availabilityBlock->property);
        $availabilityBlock->delete();

        return response()->json(null, 204);
    }
}
