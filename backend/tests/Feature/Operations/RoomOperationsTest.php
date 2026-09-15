<?php

use App\Enums\CleaningTaskStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\RoomStatus;
use App\Models\CleaningTask;
use App\Models\Guest;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->property = Property::query()->firstOrFail();
    $this->room = Room::query()->where('property_id', $this->property->id)->firstOrFail();
    $this->reception = User::query()->where('email', 'reception@example.test')->firstOrFail();
    $this->cleaner = User::query()->where('email', 'cleaning@example.test')->firstOrFail();
    $this->maintenance = User::query()->where('email', 'maintenance@example.test')->firstOrFail();
});

it('creates a cleaning task at checkout and returns the room to ready after cleaning', function (): void {
    $guest = Guest::query()->create([
        'first_name' => 'Anna',
        'last_name' => 'Verdi',
        'email' => 'anna@example.test',
        'country' => 'IT',
    ]);
    $guest->properties()->attach($this->property);
    Sanctum::actingAs($this->reception);
    $reservationResponse = $this->postJson('/api/v1/reservations', [
        'room_id' => $this->room->id,
        'primary_guest_id' => $guest->id,
        'check_in_date' => '2026-10-20',
        'check_out_date' => '2026-10-22',
        'adults' => 1,
        'children' => 0,
    ])->assertCreated();
    $reservation = Reservation::query()->findOrFail($reservationResponse->json('data.id'));

    $this->postJson("/api/v1/reservations/{$reservation->id}/check-in", [
        'keys_delivered' => true,
    ])->assertCreated();
    $this->postJson("/api/v1/reservations/{$reservation->id}/check-out", [
        'keys_returned' => true,
        'condition_notes' => null,
        'damages_amount' => '0.00',
    ])->assertCreated();

    $task = CleaningTask::query()->where('reservation_id', $reservation->id)->firstOrFail();
    expect($task->status)->toBe(CleaningTaskStatus::PENDING)
        ->and($this->room->fresh()->status)->toBe(RoomStatus::DIRTY);

    Sanctum::actingAs($this->cleaner);
    $this->postJson("/api/v1/cleaning-tasks/{$task->id}/start")
        ->assertOk()
        ->assertJsonPath('data.status', 'IN_PROGRESS');
    expect($this->room->fresh()->status)->toBe(RoomStatus::CLEANING);

    $this->postJson("/api/v1/cleaning-tasks/{$task->id}/complete", [
        'completion_notes' => 'Camera sanificata e controllata.',
    ])->assertOk()->assertJsonPath('data.status', 'COMPLETED');

    expect($this->room->fresh()->status)->toBe(RoomStatus::READY);
});

it('blocks availability for urgent maintenance until the ticket is resolved', function (): void {
    Sanctum::actingAs($this->reception);
    $response = $this->postJson('/api/v1/maintenance-tickets', [
        'room_id' => $this->room->id,
        'title' => 'Perdita bagno',
        'description' => 'Perdita visibile sotto il lavabo.',
        'priority' => 'URGENT',
        'blocks_room' => true,
        'assigned_to' => null,
    ])->assertCreated()->assertJsonPath('data.status', 'OPEN');
    $ticket = MaintenanceTicket::query()->findOrFail($response->json('data.id'));

    expect($this->room->fresh()->status)->toBe(RoomStatus::MAINTENANCE)
        ->and($ticket->availability_block_id)->not->toBeNull();

    $query = http_build_query([
        'property_id' => $this->property->id,
        'check_in_date' => '2026-11-02',
        'check_out_date' => '2026-11-04',
        'adults' => 1,
        'children' => 0,
    ]);
    $blocked = $this->getJson('/api/v1/availability?'.$query)->assertOk();
    expect(collect($blocked->json('data'))->pluck('id'))->not->toContain($this->room->id);

    Sanctum::actingAs($this->maintenance);
    $this->postJson("/api/v1/maintenance-tickets/{$ticket->id}/start")
        ->assertOk()
        ->assertJsonPath('data.status', 'IN_PROGRESS');
    $this->postJson("/api/v1/maintenance-tickets/{$ticket->id}/resolve", [
        'resolution_notes' => 'Sifone sostituito e collaudato.',
    ])->assertOk()->assertJsonPath('data.status', 'RESOLVED');

    expect($ticket->fresh()->status)->toBe(MaintenanceStatus::RESOLVED)
        ->and($this->room->fresh()->status)->toBe(RoomStatus::READY);

    $available = $this->getJson('/api/v1/availability?'.$query)->assertOk();
    expect(collect($available->json('data'))->pluck('id'))->toContain($this->room->id);
});

it('does not let cleaning staff create manual cleaning tasks', function (): void {
    Sanctum::actingAs($this->cleaner);

    $this->postJson('/api/v1/cleaning-tasks', [
        'room_id' => $this->room->id,
        'scheduled_for' => now()->addHour()->toISOString(),
        'priority' => 3,
        'notes' => 'Richiesta non autorizzata',
    ])->assertForbidden();
});
