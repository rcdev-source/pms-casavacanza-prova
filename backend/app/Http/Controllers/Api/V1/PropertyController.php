<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PropertyRequest;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PropertyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Property::class);

        return response()->json([
            'data' => $request->user()->properties()->orderBy('name')->get(),
        ]);
    }

    public function store(PropertyRequest $request): JsonResponse
    {
        Gate::authorize('create', Property::class);

        $property = DB::transaction(function () use ($request): Property {
            $property = Property::query()->create($request->validated());
            $property->users()->attach($request->user()->id);

            return $property;
        });

        return response()->json(['data' => $property], 201);
    }

    public function show(Property $property): JsonResponse
    {
        Gate::authorize('view', $property);

        return response()->json(['data' => $property->load('rooms.amenities')]);
    }

    public function update(PropertyRequest $request, Property $property): JsonResponse
    {
        Gate::authorize('update', $property);
        $property->update($request->validated());

        return response()->json(['data' => $property->fresh()]);
    }
}
