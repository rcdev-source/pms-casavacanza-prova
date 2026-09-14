<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GuestRequest;
use App\Models\Guest;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class GuestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
            'search' => ['nullable', 'string', 'max:120'],
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        $guests = $property->guests()
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->paginate(25);

        return response()->json($guests);
    }

    public function store(GuestRequest $request): JsonResponse
    {
        Gate::authorize('create', Guest::class);
        $property = Property::query()->findOrFail($request->validated('property_id'));
        Gate::authorize('view', $property);

        $guest = DB::transaction(function () use ($request, $property): Guest {
            $data = Arr::except($request->validated(), 'property_id');
            $guest = Guest::query()->create($data);
            $guest->properties()->attach($property->id);

            return $guest;
        });

        return response()->json(['data' => $guest], 201);
    }

    public function show(Guest $guest): JsonResponse
    {
        Gate::authorize('view', $guest);

        return response()->json(['data' => $guest->load('documents')]);
    }

    public function update(GuestRequest $request, Guest $guest): JsonResponse
    {
        Gate::authorize('update', $guest);
        abort_unless($guest->properties()->whereKey($request->validated('property_id'))->exists(), 422);

        $guest->update(Arr::except($request->validated(), 'property_id'));

        return response()->json(['data' => $guest->fresh()]);
    }
}
