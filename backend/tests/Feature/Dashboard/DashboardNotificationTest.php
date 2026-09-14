<?php

use App\Models\Guest;
use App\Models\Notification;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-10 10:00:00');
    $this->seed(DatabaseSeeder::class);
    $this->property = Property::query()->firstOrFail();
    $this->room = Room::query()->where('property_id', $this->property->id)->firstOrFail();
    $this->guest = Guest::query()->create([
        'first_name' => 'Luca',
        'last_name' => 'Neri',
        'email' => 'luca@example.test',
        'country' => 'IT',
    ]);
    $this->guest->properties()->attach($this->property);
    $this->reception = User::query()->where('email', 'reception@example.test')->firstOrFail();
    Sanctum::actingAs($this->reception);
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('returns live dashboard metrics and operational lists', function (): void {
    $this->postJson('/api/v1/reservations', [
        'room_id' => $this->room->id,
        'primary_guest_id' => $this->guest->id,
        'check_in_date' => '2026-10-10',
        'check_out_date' => '2026-10-12',
        'adults' => 1,
        'children' => 0,
    ])->assertCreated();

    $this->getJson('/api/v1/dashboard?property_id='.$this->property->id)
        ->assertOk()
        ->assertJsonPath('data.date', '2026-10-10')
        ->assertJsonPath('data.metrics.arrivals', 1)
        ->assertJsonPath('data.metrics.departures', 0)
        ->assertJsonPath('data.metrics.occupancy_percent', 25)
        ->assertJsonPath('data.metrics.outstanding', '170.00')
        ->assertJsonCount(1, 'data.arrivals')
        ->assertJsonCount(1, 'data.recent_reservations');
});

it('creates personal notifications and lets only their owner read them', function (): void {
    $this->postJson('/api/v1/reservations', [
        'room_id' => $this->room->id,
        'primary_guest_id' => $this->guest->id,
        'check_in_date' => '2026-10-10',
        'check_out_date' => '2026-10-12',
        'adults' => 1,
        'children' => 0,
    ])->assertCreated();

    $response = $this->getJson('/api/v1/notifications?property_id='.$this->property->id.'&unread=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'reservation.created');
    $notification = Notification::query()->findOrFail($response->json('data.0.id'));

    $this->postJson("/api/v1/notifications/{$notification->id}/read")
        ->assertOk()
        ->assertJsonPath('data.id', $notification->id);
    expect($notification->fresh()->read_at)->not->toBeNull();

    $otherNotification = Notification::query()
        ->where('user_id', '!=', $this->reception->id)
        ->firstOrFail();
    $this->postJson("/api/v1/notifications/{$otherNotification->id}/read")->assertNotFound();
});

it('marks all current user notifications as read without touching other users', function (): void {
    foreach (range(1, 2) as $index) {
        Notification::query()->create([
            'property_id' => $this->property->id,
            'user_id' => $this->reception->id,
            'type' => 'test',
            'title' => 'Test '.$index,
            'message' => 'Messaggio',
        ]);
    }

    $this->postJson('/api/v1/notifications/read-all', [
        'property_id' => $this->property->id,
    ])->assertOk();

    expect(Notification::query()
        ->where('user_id', $this->reception->id)
        ->whereNull('read_at')
        ->count())->toBe(0);
});
