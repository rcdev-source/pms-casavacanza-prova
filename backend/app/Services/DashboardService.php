<?php

namespace App\Services;

use App\Enums\CleaningTaskStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\CleaningTask;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;

class DashboardService
{
    public function forProperty(Property $property): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $activeStatuses = ReservationStatus::blockingValues();
        $roomsTotal = $property->rooms()->where('is_active', true)->count();
        $occupied = Reservation::query()
            ->where('property_id', $property->id)
            ->whereIn('status', $activeStatuses)
            ->where('check_in_date', '<=', $today)
            ->where('check_out_date', '>', $today)
            ->distinct('room_id')
            ->count('room_id');

        $arrivals = Reservation::query()
            ->with('room', 'primaryGuest')
            ->where('property_id', $property->id)
            ->whereIn('status', [ReservationStatus::CONFIRMED, ReservationStatus::PRE_CHECKIN])
            ->whereDate('check_in_date', $today)
            ->orderBy('check_in_date')
            ->get();
        $departures = Reservation::query()
            ->with('room', 'primaryGuest')
            ->where('property_id', $property->id)
            ->whereIn('status', [ReservationStatus::CHECKED_IN, ReservationStatus::IN_HOUSE])
            ->whereDate('check_out_date', $today)
            ->orderBy('check_out_date')
            ->get();

        return [
            'date' => $today,
            'metrics' => [
                'arrivals' => $arrivals->count(),
                'departures' => $departures->count(),
                'occupancy_percent' => $roomsTotal > 0 ? (int) round(($occupied / $roomsTotal) * 100) : 0,
                'rooms_total' => $roomsTotal,
                'rooms_occupied' => $occupied,
                'cleaning_open' => CleaningTask::query()
                    ->where('property_id', $property->id)
                    ->whereIn('status', [
                        CleaningTaskStatus::PENDING,
                        CleaningTaskStatus::ASSIGNED,
                        CleaningTaskStatus::IN_PROGRESS,
                    ])->count(),
                'maintenance_open' => MaintenanceTicket::query()
                    ->where('property_id', $property->id)
                    ->whereIn('status', [MaintenanceStatus::OPEN, MaintenanceStatus::IN_PROGRESS])
                    ->count(),
                'revenue_month' => $this->sumMoney(
                    Payment::query()
                        ->where('property_id', $property->id)
                        ->where('status', PaymentStatus::COMPLETED)
                        ->whereBetween('paid_at', [$monthStart, $monthEnd])
                        ->pluck('amount'),
                ),
                'outstanding' => $this->sumMoney(
                    Reservation::query()
                        ->where('property_id', $property->id)
                        ->whereNotIn('status', [ReservationStatus::CANCELLED, ReservationStatus::COMPLETED])
                        ->pluck('balance_due'),
                ),
            ],
            'arrivals' => $arrivals,
            'departures' => $departures,
            'recent_reservations' => Reservation::query()
                ->with('room', 'primaryGuest')
                ->where('property_id', $property->id)
                ->latest()
                ->limit(8)
                ->get(),
        ];
    }

    private function sumMoney($amounts): string
    {
        return $amounts->reduce(
            fn (string $carry, $amount): string => bcadd($carry, (string) $amount, 2),
            '0.00',
        );
    }
}
