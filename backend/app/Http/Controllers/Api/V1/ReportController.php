<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function financial(Request $request): JsonResponse
    {
        [$property, $from, $to] = $this->context($request);
        $reservations = Reservation::query()
            ->where('property_id', $property->id)
            ->where('status', '!=', ReservationStatus::CANCELLED)
            ->where('check_in_date', '<', $to)
            ->where('check_out_date', '>', $from);

        return response()->json([
            'data' => [
                'from' => $from,
                'to' => $to,
                'currency' => $property->currency,
                'reservations' => (clone $reservations)->count(),
                'booked_total' => $this->sumMoney((clone $reservations)->pluck('total')),
                'outstanding' => $this->sumMoney((clone $reservations)->pluck('balance_due')),
                'payments_received' => $this->sumMoney(
                    Payment::query()
                        ->where('property_id', $property->id)
                        ->where('status', PaymentStatus::COMPLETED)
                        ->whereDate('paid_at', '>=', $from)
                        ->whereDate('paid_at', '<=', $to)
                        ->pluck('amount'),
                ),
            ],
        ]);
    }

    public function reservationsCsv(Request $request): StreamedResponse
    {
        [$property, $from, $to] = $this->context($request);
        $reservations = Reservation::query()
            ->with('room', 'primaryGuest')
            ->where('property_id', $property->id)
            ->where('check_in_date', '<', $to)
            ->where('check_out_date', '>', $from)
            ->orderBy('check_in_date')
            ->get();

        return response()->streamDownload(function () use ($reservations): void {
            $output = fopen('php://output', 'wb');
            fputcsv($output, [
                'Codice',
                'Ospite',
                'Camera',
                'Check-in',
                'Check-out',
                'Stato',
                'Totale',
                'Saldo',
            ], ',', '"', '');

            foreach ($reservations as $reservation) {
                fputcsv($output, [
                    $this->csvValue($reservation->booking_code),
                    $this->csvValue($reservation->primaryGuest->last_name.' '.$reservation->primaryGuest->first_name),
                    $this->csvValue($reservation->room->name),
                    $reservation->check_in_date->toDateString(),
                    $reservation->check_out_date->toDateString(),
                    $reservation->status->value,
                    $reservation->total,
                    $reservation->balance_due,
                ], ',', '"', '');
            }

            fclose($output);
        }, "prenotazioni-{$from}-{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function context(Request $request): array
    {
        $validated = $request->validate([
            'property_id' => ['required', 'ulid', 'exists:properties,id'],
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $property = Property::query()->findOrFail($validated['property_id']);
        Gate::authorize('view', $property);
        abort_unless(
            $request->user()->hasRole(Role::ADMIN) || $request->user()->hasRole(Role::RECEPTION),
            403,
        );

        return [$property, $validated['from'], $validated['to']];
    }

    private function sumMoney($amounts): string
    {
        return $amounts->reduce(
            fn (string $carry, $amount): string => bcadd($carry, (string) $amount, 2),
            '0.00',
        );
    }

    private function csvValue(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
