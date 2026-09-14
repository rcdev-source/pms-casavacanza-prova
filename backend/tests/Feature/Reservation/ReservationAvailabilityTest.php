<?php

use App\Models\AvailabilityBlock;
use App\Models\Guest;
use App\Models\PricingRule;
use App\Models\Property;
use App\Models\Room;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->property = Property::query()->firstOrFail();
    $this->room = Room::query()->where('property_id', $this->property->id)->firstOrFail();
    $this->guest = Guest::query()->create([
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'email' => 'mario@example.test',
        'country' => 'IT',
    ]);
    $this->guest->properties()->attach($this->property);
    $this->reception = User::query()->where('email', 'reception@example.test')->firstOrFail();
    Sanctum::actingAs($this->reception);

    $this->payload = [
        'room_id' => $this->room->id,
        'primary_guest_id' => $this->guest->id,
        'check_in_date' => '2026-10-01',
        'check_out_date' => '2026-10-05',
        'adults' => 2,
        'children' => 0,
    ];
});

it('prevents overlapping reservations but allows adjacent stays', function (): void {
    $this->postJson('/api/v1/reservations', $this->payload)
        ->assertCreated()
        ->assertJsonPath('data.total', '340.00');

    $overlapping = [
        ...$this->payload,
        'check_in_date' => '2026-10-03',
        'check_out_date' => '2026-10-07',
    ];
    $this->postJson('/api/v1/reservations', $overlapping)
        ->assertStatus(409)
        ->assertJsonPath('code', 'ROOM_NOT_AVAILABLE');

    $adjacent = [
        ...$this->payload,
        'check_in_date' => '2026-10-05',
        'check_out_date' => '2026-10-07',
    ];
    $this->postJson('/api/v1/reservations', $adjacent)->assertCreated();
});

it('applies seasonal prices and minimum stays on the server', function (): void {
    PricingRule::query()->create([
        'property_id' => $this->property->id,
        'room_id' => $this->room->id,
        'name' => 'Alta stagione',
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-30',
        'price_per_night' => '150.00',
        'minimum_stay' => 2,
        'priority' => 100,
        'is_active' => true,
    ]);

    $oneNight = [
        ...$this->payload,
        'check_in_date' => '2026-11-10',
        'check_out_date' => '2026-11-11',
    ];
    $this->postJson('/api/v1/reservations', $oneNight)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('check_out_date');

    $twoNights = [...$oneNight, 'check_out_date' => '2026-11-12'];
    $this->postJson('/api/v1/reservations', $twoNights)
        ->assertCreated()
        ->assertJsonPath('data.subtotal', '300.00');
});

it('excludes blocked rooms and rooms below requested capacity', function (): void {
    AvailabilityBlock::query()->create([
        'property_id' => $this->property->id,
        'room_id' => $this->room->id,
        'start_date' => '2026-12-01',
        'end_date' => '2026-12-05',
        'reason' => 'Lavori',
    ]);

    $response = $this->getJson('/api/v1/availability?'.http_build_query([
        'property_id' => $this->property->id,
        'check_in_date' => '2026-12-02',
        'check_out_date' => '2026-12-04',
        'adults' => 2,
        'children' => 0,
    ]))->assertOk();

    expect(collect($response->json('data'))->pluck('id'))->not->toContain($this->room->id);

    $tooMany = [...$this->payload, 'adults' => 3];
    $this->postJson('/api/v1/reservations', $tooMany)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('adults');
});

it('returns room lanes and reservations for the calendar interval', function (): void {
    $this->postJson('/api/v1/reservations', $this->payload)->assertCreated();

    $this->getJson('/api/v1/calendar?'.http_build_query([
        'property_id' => $this->property->id,
        'from' => '2026-10-01',
        'to' => '2026-11-01',
    ]))
        ->assertOk()
        ->assertJsonPath('data.from', '2026-10-01')
        ->assertJsonCount(4, 'data.rooms')
        ->assertJsonCount(1, 'data.rooms.0.reservations');
});

it('denies reservation management to cleaning staff', function (): void {
    $cleaner = User::query()->where('email', 'cleaning@example.test')->firstOrFail();
    Sanctum::actingAs($cleaner);

    $this->getJson('/api/v1/reservations?'.http_build_query([
        'property_id' => $this->property->id,
    ]))->assertForbidden();
});
