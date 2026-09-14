<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
            'unread' => ['sometimes', 'boolean'],
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        return response()->json([
            'data' => Notification::query()
                ->where('property_id', $property->id)
                ->where('user_id', $request->user()->id)
                ->when($validated['unread'] ?? false, fn ($query) => $query->whereNull('read_at'))
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function read(Request $request, Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 404);
        Gate::authorize('view', $notification->property);
        $notification->update(['read_at' => $notification->read_at ?? now()]);

        return response()->json(['data' => $notification->fresh()]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        Notification::query()
            ->where('property_id', $property->id)
            ->where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'Notifiche segnate come lette.']);
    }
}
