<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CleaningTaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CleaningTaskRequest;
use App\Http\Requests\Api\V1\CompleteCleaningTaskRequest;
use App\Models\CleaningTask;
use App\Models\Property;
use App\Models\Room;
use App\Services\CleaningTaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CleaningTaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', CleaningTask::class);
        $validated = $request->validate([
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
            'status' => ['nullable', Rule::enum(CleaningTaskStatus::class)],
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);

        return response()->json([
            'data' => CleaningTask::query()
                ->with('room', 'assignee')
                ->where('property_id', $property->id)
                ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->orderByDesc('priority')
                ->orderBy('scheduled_for')
                ->get(),
        ]);
    }

    public function store(CleaningTaskRequest $request, CleaningTaskService $tasks): JsonResponse
    {
        Gate::authorize('create', CleaningTask::class);
        $room = Room::query()->with('property')->findOrFail($request->validated('room_id'));
        Gate::authorize('view', $room->property);

        return response()->json([
            'data' => $tasks->create($request->validated(), $request->user())->load('room', 'assignee'),
        ], 201);
    }

    public function show(CleaningTask $cleaningTask): JsonResponse
    {
        Gate::authorize('view', $cleaningTask);

        return response()->json(['data' => $cleaningTask->load('room', 'reservation', 'assignee')]);
    }

    public function start(CleaningTask $cleaningTask, CleaningTaskService $tasks): JsonResponse
    {
        Gate::authorize('update', $cleaningTask);

        return response()->json(['data' => $tasks->start($cleaningTask, request()->user())]);
    }

    public function complete(
        CompleteCleaningTaskRequest $request,
        CleaningTask $cleaningTask,
        CleaningTaskService $tasks,
    ): JsonResponse {
        Gate::authorize('update', $cleaningTask);

        return response()->json(['data' => $tasks->complete($cleaningTask, $request->validated())]);
    }
}
