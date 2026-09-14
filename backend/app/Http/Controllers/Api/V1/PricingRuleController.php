<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PricingRuleRequest;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PricingRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['property_id' => ['required', 'ulid', 'exists:properties,id']]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        return response()->json([
            'data' => $property->pricingRules()->with('room')->orderByDesc('priority')->get(),
        ]);
    }

    public function store(PricingRuleRequest $request): JsonResponse
    {
        $property = Property::query()->findOrFail($request->validated('property_id'));
        Gate::authorize('update', $property);
        $this->guardRoom($request->validated('room_id'), $property);

        return response()->json(['data' => PricingRule::query()->create($request->validated())], 201);
    }

    public function update(PricingRuleRequest $request, PricingRule $pricingRule): JsonResponse
    {
        Gate::authorize('update', $pricingRule->property);
        abort_unless(
            $request->validated('property_id') === $pricingRule->property_id,
            422,
            'La regola non può cambiare struttura.',
        );
        $this->guardRoom($request->validated('room_id'), $pricingRule->property);
        $pricingRule->update($request->validated());

        return response()->json(['data' => $pricingRule->fresh()->load('room')]);
    }

    public function destroy(PricingRule $pricingRule): JsonResponse
    {
        Gate::authorize('update', $pricingRule->property);
        $pricingRule->delete();

        return response()->json(null, 204);
    }

    private function guardRoom(?string $roomId, Property $property): void
    {
        if ($roomId === null) {
            return;
        }

        abort_unless(
            Room::query()->whereKey($roomId)->where('property_id', $property->id)->exists(),
            422,
            'La camera non appartiene alla struttura.',
        );
    }
}
