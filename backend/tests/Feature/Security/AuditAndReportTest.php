<?php

use App\Models\AuditLog;
use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function (): void {
    Carbon::setTestNow('2026-10-10 12:00:00');
    $this->seed(DatabaseSeeder::class);
    $this->property = Property::query()->firstOrFail();
    $this->room = Room::query()->where('property_id', $this->property->id)->firstOrFail();
    $this->guest = Guest::query()->create([
        'first_name' => 'Sara',
        'last_name' => 'Blu',
        'email' => 'sara@example.test',
        'country' => 'IT',
    ]);
    $this->guest->properties()->attach($this->property);
    $this->reception = User::query()->where('email', 'reception@example.test')->firstOrFail();
    Sanctum::actingAs($this->reception);

    $response = $this->postJson('/api/v1/reservations', [
        'room_id' => $this->room->id,
        'primary_guest_id' => $this->guest->id,
        'check_in_date' => '2026-10-10',
        'check_out_date' => '2026-10-12',
        'adults' => 1,
        'children' => 0,
    ])->assertCreated();
    $this->reservation = Reservation::query()->findOrFail($response->json('data.id'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('writes append-only audit entries for critical business changes', function (): void {
    $created = AuditLog::query()
        ->where('auditable_type', Reservation::class)
        ->where('auditable_id', $this->reservation->id)
        ->where('event', 'created')
        ->firstOrFail();

    expect($created->property_id)->toBe($this->property->id)
        ->and($created->user_id)->toBe($this->reception->id)
        ->and($created->new_values['booking_code'])->toBe($this->reservation->booking_code);

    $this->room->update(['description' => 'Descrizione aggiornata']);
    $updated = AuditLog::query()
        ->where('auditable_type', Room::class)
        ->where('auditable_id', $this->room->id)
        ->where('event', 'updated')
        ->latest('created_at')
        ->firstOrFail();

    expect($updated->old_values)->toHaveKey('description')
        ->and($updated->new_values['description'])->toBe('Descrizione aggiornata');
});

it('returns exact financial totals and a safe csv export', function (): void {
    $this->postJson("/api/v1/reservations/{$this->reservation->id}/payments", [
        'amount' => '50.00',
        'method' => 'CARD',
    ])->assertCreated();

    $query = http_build_query([
        'property_id' => $this->property->id,
        'from' => '2026-10-01',
        'to' => '2026-10-31',
    ]);
    $this->getJson('/api/v1/reports/financial?'.$query)
        ->assertOk()
        ->assertJsonPath('data.reservations', 1)
        ->assertJsonPath('data.booked_total', '170.00')
        ->assertJsonPath('data.payments_received', '50.00')
        ->assertJsonPath('data.outstanding', '120.00');

    $response = $this->get('/api/v1/reports/reservations.csv?'.$query)
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($response->streamedContent())
        ->toContain($this->reservation->booking_code)
        ->toContain('Sara');
});

it('denies financial reports to operational cleaning users', function (): void {
    $cleaner = User::query()->where('email', 'cleaning@example.test')->firstOrFail();
    Sanctum::actingAs($cleaner);

    $this->getJson('/api/v1/reports/financial?'.http_build_query([
        'property_id' => $this->property->id,
        'from' => '2026-10-01',
        'to' => '2026-10-31',
    ]))->assertForbidden();
});
