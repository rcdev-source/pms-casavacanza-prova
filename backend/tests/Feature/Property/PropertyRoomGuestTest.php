<?php

use App\Enums\RoomStatus;
use App\Models\Property;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    $this->seed(DatabaseSeeder::class);
});

it('seeds one property and four rooms', function (): void {
    $property = Property::query()->with('rooms')->firstOrFail();

    expect($property->rooms)->toHaveCount(4);
});

it('allows an admin to create a room in an assigned property', function (): void {
    $admin = User::query()->where('email', 'admin@example.test')->firstOrFail();
    $property = $admin->properties()->firstOrFail();
    Sanctum::actingAs($admin);

    $this->postJson('/api/v1/rooms', [
        'property_id' => $property->id,
        'name' => 'Suite',
        'code' => 'SUITE',
        'max_guests' => 3,
        'max_adults' => 2,
        'max_children' => 1,
        'base_price' => '140.00',
        'status' => RoomStatus::READY->value,
        'is_active' => true,
    ])->assertCreated()->assertJsonPath('data.code', 'SUITE');
});

it('prevents a cleaning user from configuring rooms', function (): void {
    $cleaner = User::query()->where('email', 'cleaning@example.test')->firstOrFail();
    $property = $cleaner->properties()->firstOrFail();
    Sanctum::actingAs($cleaner);

    $this->postJson('/api/v1/rooms', [
        'property_id' => $property->id,
        'name' => 'Unauthorized',
        'code' => 'NOPE',
        'max_guests' => 2,
        'max_adults' => 2,
        'max_children' => 0,
        'base_price' => '10.00',
        'status' => RoomStatus::READY->value,
        'is_active' => true,
    ])->assertForbidden();
});

it('blocks cross property access even for an admin', function (): void {
    $admin = User::query()->where('email', 'admin@example.test')->firstOrFail();
    $otherProperty = Property::query()->create([
        'name' => 'Other',
        'address' => 'Other street',
        'city' => 'Catania',
        'postal_code' => '95100',
        'province' => 'CT',
        'country' => 'IT',
        'default_check_in_time' => '15:00',
        'default_check_out_time' => '10:00',
        'currency' => 'EUR',
        'timezone' => 'Europe/Rome',
    ]);
    Sanctum::actingAs($admin);

    $this->getJson("/api/v1/properties/{$otherProperty->id}")->assertForbidden();
});

it('allows reception to create a guest for an assigned property', function (): void {
    $reception = User::query()->where('email', 'reception@example.test')->firstOrFail();
    $property = $reception->properties()->firstOrFail();
    Sanctum::actingAs($reception);

    $this->postJson('/api/v1/guests', [
        'property_id' => $property->id,
        'first_name' => 'Mario',
        'last_name' => 'Rossi',
        'email' => 'mario.rossi@example.test',
        'country' => 'IT',
    ])->assertCreated()->assertJsonPath('data.last_name', 'Rossi');
});

it('assigns seeded users to the demo property', function (): void {
    expect(User::query()->count())->toBe(4)
        ->and(User::query()->whereHas('properties')->count())->toBe(4)
        ->and(Role::query()->count())->toBe(4);
});
