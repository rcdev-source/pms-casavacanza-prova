<?php

namespace App\Observers;

use App\Models\CleaningTask;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Model;

class OperationalNotificationObserver
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function created(Model $model): void
    {
        match (true) {
            $model instanceof Reservation => $this->notifications->broadcast(
                $model->property_id,
                'reservation.created',
                'Nuova prenotazione',
                "{$model->booking_code} è stata registrata.",
                "/reservations/{$model->id}",
            ),
            $model instanceof CleaningTask => $this->notifications->broadcast(
                $model->property_id,
                'cleaning.created',
                'Pulizia da eseguire',
                "È stata aggiunta una pulizia per {$model->room->name}.",
                '/cleaning',
            ),
            $model instanceof MaintenanceTicket => $this->notifications->broadcast(
                $model->property_id,
                'maintenance.created',
                $model->blocks_room ? 'Camera bloccata per manutenzione' : 'Nuova manutenzione',
                $model->title,
                '/maintenance',
            ),
            $model instanceof Payment => $this->notifications->broadcast(
                $model->property_id,
                'payment.created',
                'Pagamento registrato',
                "{$model->amount} per la prenotazione {$model->reservation->booking_code}.",
                "/reservations/{$model->reservation_id}",
            ),
            default => null,
        };
    }

    public function updated(Model $model): void
    {
        if (! $model->wasChanged('status')) {
            return;
        }

        match (true) {
            $model instanceof Reservation => $this->notifications->broadcast(
                $model->property_id,
                'reservation.status',
                'Stato prenotazione aggiornato',
                "{$model->booking_code}: {$model->status->value}.",
                "/reservations/{$model->id}",
            ),
            $model instanceof CleaningTask => $this->notifications->broadcast(
                $model->property_id,
                'cleaning.status',
                'Pulizia aggiornata',
                "{$model->room->name}: {$model->status->value}.",
                '/cleaning',
            ),
            $model instanceof MaintenanceTicket => $this->notifications->broadcast(
                $model->property_id,
                'maintenance.status',
                'Manutenzione aggiornata',
                "{$model->title}: {$model->status->value}.",
                '/maintenance',
            ),
            $model instanceof Payment => $this->notifications->broadcast(
                $model->property_id,
                'payment.status',
                'Pagamento aggiornato',
                "{$model->amount}: {$model->status->value}.",
                "/reservations/{$model->reservation_id}",
            ),
            default => null,
        };
    }
}
