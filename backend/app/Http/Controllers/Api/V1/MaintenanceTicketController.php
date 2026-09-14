<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\MaintenanceTicketRequest;
use App\Http\Requests\Api\V1\ResolveMaintenanceTicketRequest;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\Room;
use App\Services\MaintenanceTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MaintenanceTicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', MaintenanceTicket::class);
        $validated = $request->validate([
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
            'status' => ['nullable', Rule::enum(MaintenanceStatus::class)],
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        return response()->json([
            'data' => MaintenanceTicket::query()
                ->with('room', 'assignee', 'reporter')
                ->where('property_id', $property->id)
                ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->orderByRaw("FIELD(priority, 'URGENT', 'HIGH', 'NORMAL', 'LOW')")
                ->latest()
                ->get(),
        ]);
    }

    public function store(
        MaintenanceTicketRequest $request,
        MaintenanceTicketService $tickets,
    ): JsonResponse {
        Gate::authorize('create', MaintenanceTicket::class);
        $room = Room::query()->with('property')->findOrFail($request->validated('room_id'));
        Gate::authorize('view', $room->property);

        return response()->json([
            'data' => $tickets->create($request->validated(), $request->user()),
        ], 201);
    }

    public function show(MaintenanceTicket $maintenanceTicket): JsonResponse
    {
        Gate::authorize('view', $maintenanceTicket);

        return response()->json([
            'data' => $maintenanceTicket->load('room', 'assignee', 'reporter'),
        ]);
    }

    public function start(
        MaintenanceTicket $maintenanceTicket,
        MaintenanceTicketService $tickets,
    ): JsonResponse {
        Gate::authorize('update', $maintenanceTicket);

        return response()->json([
            'data' => $tickets->start($maintenanceTicket, request()->user()),
        ]);
    }

    public function resolve(
        ResolveMaintenanceTicketRequest $request,
        MaintenanceTicket $maintenanceTicket,
        MaintenanceTicketService $tickets,
    ): JsonResponse {
        Gate::authorize('update', $maintenanceTicket);

        return response()->json([
            'data' => $tickets->resolve($maintenanceTicket, $request->validated()),
        ]);
    }

    public function close(
        MaintenanceTicket $maintenanceTicket,
        MaintenanceTicketService $tickets,
    ): JsonResponse {
        Gate::authorize('update', $maintenanceTicket);

        return response()->json(['data' => $tickets->close($maintenanceTicket)]);
    }
}
