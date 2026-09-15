<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RoomRequest;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['property_id' => ['required', 'ulid', 'exists:properties,id']]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        return response()->json([
            'data' => $property->rooms()->with('amenities')->orderBy('name')->get(),
        ]);
    }

    public function store(RoomRequest $request): JsonResponse
    {
        Gate::authorize('create', Room::class);
        $property = Property::query()->findOrFail($request->validated('property_id'));
        Gate::authorize('update', $property);

        $room = DB::transaction(function () use ($request): Room {
            $data = $request->validated();
            $room = Room::query()->create(Arr::except($data, 'amenity_ids'));
            $room->amenities()->sync($data['amenity_ids'] ?? []);

            return $room;
        });

        return response()->json(['data' => $room->load('amenities')], 201);
    }

    public function show(Room $room): JsonResponse
    {
        Gate::authorize('view', $room);

        return response()->json(['data' => $room->load('property', 'amenities')]);
    }

    public function update(RoomRequest $request, Room $room): JsonResponse
    {
        Gate::authorize('update', $room);

        abort_unless($request->validated('property_id') === $room->property_id, 422, 'La camera non può cambiare struttura.');

        DB::transaction(function () use ($request, $room): void {
            $data = $request->validated();
            $room->update(Arr::except($data, 'amenity_ids'));
            $room->amenities()->sync($data['amenity_ids'] ?? []);
        });

        return response()->json(['data' => $room->fresh()->load('amenities')]);
    }
}
