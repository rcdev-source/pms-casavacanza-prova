<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        return response()->json(['data' => $dashboard->forProperty($property)]);
    }
}
