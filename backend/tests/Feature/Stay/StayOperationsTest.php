<?php

use App\Enums\ReservationStatus;
use App\Enums\RoomStatus;
use App\Models\Guest;
use App\Models\Payment;
use App\Models\PreCheckInToken;
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
    $this->guest = Guest::query()->create([
        'first_name' => 'Giulia',
        'last_name' => 'Bianchi',
        'email' => 'giulia@example.test',
        'country' => 'IT',
    ]);
    $this->guest->properties()->attach($this->property);
    $this->reception = User::query()->where('email', 'reception@example.test')->firstOrFail();
    Sanctum::actingAs($this->reception);

    $response = $this->postJson('/api/v1/reservations', [
        'room_id' => $this->room->id,
        'primary_guest_id' => $this->guest->id,
        'check_in_date' => '2026-10-10',
        'check_out_date' => '2026-10-14',
        'adults' => 2,
        'children' => 0,
    ])->assertCreated();

    $this->reservation = Reservation::query()->findOrFail($response->json('data.id'));
});

it('recalculates totals through extras payments and refunds', function (): void {
    $this->postJson("/api/v1/reservations/{$this->reservation->id}/services", [
        'description' => 'Colazione',
        'quantity' => '2.00',
        'unit_price' => '15.00',
    ])->assertCreated()->assertJsonPath('data.total', '30.00');

    $this->getJson("/api/v1/reservations/{$this->reservation->id}")
        ->assertOk()
        ->assertJsonPath('data.total', '370.00')
        ->assertJsonPath('data.balance_due', '370.00');

    $paymentResponse = $this->postJson("/api/v1/reservations/{$this->reservation->id}/payments", [
        'amount' => '100.00',
        'method' => 'CARD',
        'transaction_reference' => 'POS-001',
    ])->assertCreated()->assertJsonPath('data.status', 'COMPLETED');

    $this->getJson("/api/v1/reservations/{$this->reservation->id}")
        ->assertJsonPath('data.balance_due', '270.00');

    $payment = Payment::query()->findOrFail($paymentResponse->json('data.id'));
    $this->postJson("/api/v1/payments/{$payment->id}/refund")
        ->assertOk()
        ->assertJsonPath('data.status', 'REFUNDED');

    $this->getJson("/api/v1/reservations/{$this->reservation->id}")
        ->assertJsonPath('data.balance_due', '370.00');
});

it('rejects payments above the outstanding balance', function (): void {
    $this->postJson("/api/v1/reservations/{$this->reservation->id}/payments", [
        'amount' => '999.00',
        'method' => 'CASH',
    ])->assertUnprocessable()->assertJsonValidationErrors('amount');
});

it('issues a hashed one-time pre-check-in link and accepts it once', function (): void {
    $response = $this->postJson("/api/v1/reservations/{$this->reservation->id}/pre-check-in-link", [
        'expires_in_hours' => 48,
    ])->assertCreated();

    $path = (string) parse_url((string) $response->json('data.url'), PHP_URL_PATH);
    $token = basename($path);
    $record = PreCheckInToken::query()->firstOrFail();

    expect($record->getRawOriginal('token_hash'))->toBe(hash('sha256', $token))
        ->and($record->getRawOriginal('token_hash'))->not->toBe($token);

    $this->getJson("/api/v1/public/pre-check-in/{$token}")
        ->assertOk()
        ->assertJsonPath('data.booking_code', $this->reservation->booking_code);

    $payload = [
        'first_name' => 'Giulia',
        'last_name' => 'Bianchi',
        'birth_date' => '1990-05-10',
        'birth_place' => 'Catania',
        'nationality' => 'IT',
        'email' => 'giulia@example.test',
        'phone' => '+39 333 1234567',
        'address' => 'Via Etnea 10',
        'city' => 'Catania',
        'postal_code' => '95124',
        'country' => 'IT',
        'document_type' => 'ID_CARD',
        'document_number' => 'CA1234567',
        'document_issuing_country' => 'IT',
        'document_expiry_date' => '2030-05-10',
    ];
    $this->postJson("/api/v1/public/pre-check-in/{$token}", $payload)
        ->assertOk()
        ->assertJsonPath('data.status', 'PRE_CHECKIN');

    $this->postJson("/api/v1/public/pre-check-in/{$token}", $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('token');

    expect($this->reservation->fresh()->status)->toBe(ReservationStatus::PRE_CHECKIN)
        ->and($record->fresh()->payload['document_number'])->toBe('CA1234567');
});

it('moves the stay through check-in and check-out and marks the room dirty', function (): void {
    $this->postJson("/api/v1/reservations/{$this->reservation->id}/check-in", [
        'keys_delivered' => true,
        'notes' => 'Documento verificato',
    ])->assertCreated();

    expect($this->reservation->fresh()->status)->toBe(ReservationStatus::CHECKED_IN)
        ->and($this->room->fresh()->status)->toBe(RoomStatus::OCCUPIED);

    $this->postJson("/api/v1/reservations/{$this->reservation->id}/check-out", [
        'keys_returned' => true,
        'condition_notes' => 'Graffio sul tavolo',
        'damages_amount' => '20.00',
    ])->assertCreated();

    expect($this->reservation->fresh()->status)->toBe(ReservationStatus::CHECKED_OUT)
        ->and($this->reservation->fresh()->extras_total)->toBe('20.00')
        ->and($this->room->fresh()->status)->toBe(RoomStatus::DIRTY);
});

it('prevents cleaning staff from recording a payment', function (): void {
    $cleaner = User::query()->where('email', 'cleaning@example.test')->firstOrFail();
    Sanctum::actingAs($cleaner);

    $this->postJson("/api/v1/reservations/{$this->reservation->id}/payments", [
        'amount' => '50.00',
        'method' => 'CASH',
    ])->assertForbidden();
});
