<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CalendarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after:from'],
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);
        $from = $validated['from'];
        $to = $validated['to'];

        $rooms = $property->rooms()
            ->with([
                'reservations' => fn (Builder $query) => $query
                    ->with('primaryGuest')
                    ->where('check_in_date', '<', $to)
                    ->where('check_out_date', '>', $from)
                    ->orderBy('check_in_date'),
                'availabilityBlocks' => fn (Builder $query) => $query
                    ->where('start_date', '<', $to)
                    ->where('end_date', '>', $from)
                    ->orderBy('start_date'),
            ])
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => [
                'from' => $from,
                'to' => $to,
                'rooms' => $rooms,
            ],
        ]);
    }
}
