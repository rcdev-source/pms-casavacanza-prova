<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AvailabilityBlockController;
use App\Http\Controllers\Api\V1\AvailabilityController;
use App\Http\Controllers\Api\V1\CalendarController;
use App\Http\Controllers\Api\V1\GuestController;
use App\Http\Controllers\Api\V1\GuestDocumentController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PreCheckInController;
use App\Http\Controllers\Api\V1\PricingRuleController;
use App\Http\Controllers\Api\V1\PropertyController;
use App\Http\Controllers\Api\V1\ReservationChargeController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\RoomController;
use App\Http\Controllers\Api\V1\StayController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'store'])->middleware('throttle:login');
    Route::get('/public/availability', AvailabilityController::class)->middleware('throttle:public-booking');
    Route::get('/public/pre-check-in/{token}', [PreCheckInController::class, 'show'])
        ->middleware('throttle:public-booking');
    Route::post('/public/pre-check-in/{token}', [PreCheckInController::class, 'complete'])
        ->middleware('throttle:public-booking');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'show']);
        Route::delete('/auth/logout', [AuthController::class, 'destroy']);

        Route::apiResource('properties', PropertyController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('rooms', RoomController::class)->only(['index', 'store', 'show', 'update']);
        Route::apiResource('guests', GuestController::class)->only(['index', 'store', 'show', 'update']);
        Route::post('/guests/{guest}/documents', [GuestDocumentController::class, 'store']);
        Route::get('/guest-documents/{document}/download', [GuestDocumentController::class, 'download'])
            ->name('guest-documents.download');

        Route::get('/availability', AvailabilityController::class);
        Route::get('/calendar', CalendarController::class);
        Route::apiResource('reservations', ReservationController::class);
        Route::apiResource('pricing-rules', PricingRuleController::class)->except(['show']);
        Route::apiResource('availability-blocks', AvailabilityBlockController::class)
            ->only(['index', 'store', 'destroy']);

        Route::get('/reservations/{reservation}/payments', [PaymentController::class, 'index']);
        Route::post('/reservations/{reservation}/payments', [PaymentController::class, 'store']);
        Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund']);
        Route::get('/reservations/{reservation}/services', [ReservationChargeController::class, 'index']);
        Route::post('/reservations/{reservation}/services', [ReservationChargeController::class, 'store']);
        Route::post('/reservations/{reservation}/pre-check-in-link', [PreCheckInController::class, 'issue']);
        Route::post('/reservations/{reservation}/check-in', [StayController::class, 'checkIn']);
        Route::post('/reservations/{reservation}/check-out', [StayController::class, 'checkOut']);
    });
});
