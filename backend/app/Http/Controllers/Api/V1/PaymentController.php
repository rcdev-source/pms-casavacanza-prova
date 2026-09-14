<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PaymentRequest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\PaymentLedgerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function index(Reservation $reservation): JsonResponse
    {
        Gate::authorize('view', $reservation);

        return response()->json([
            'data' => $reservation->payments()->with('recorder')->latest()->get(),
        ]);
    }

    public function store(
        PaymentRequest $request,
        Reservation $reservation,
        PaymentLedgerService $ledger,
    ): JsonResponse {
        Gate::authorize('update', $reservation);

        return response()->json([
            'data' => $ledger->record($reservation, $request->validated(), $request->user()),
        ], 201);
    }

    public function refund(Payment $payment, PaymentLedgerService $ledger): JsonResponse
    {
        Gate::authorize('update', $payment->reservation);

        return response()->json(['data' => $ledger->refund($payment)]);
    }
}
