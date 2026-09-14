<?php

namespace App\Providers;

use App\Models\AvailabilityBlock;
use App\Models\CheckIn;
use App\Models\CheckOut;
use App\Models\CleaningTask;
use App\Models\Guest;
use App\Models\MaintenanceTicket;
use App\Models\Payment;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\ReservationService;
use App\Models\Room;
use App\Observers\AuditLogObserver;
use App\Observers\OperationalNotificationObserver;
use App\Policies\CleaningTaskPolicy;
use App\Policies\GuestPolicy;
use App\Policies\MaintenanceTicketPolicy;
use App\Policies\PropertyPolicy;
use App\Policies\ReservationPolicy;
use App\Policies\RoomPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(Room::class, RoomPolicy::class);
        Gate::policy(Guest::class, GuestPolicy::class);
        Gate::policy(Reservation::class, ReservationPolicy::class);
        Gate::policy(CleaningTask::class, CleaningTaskPolicy::class);
        Gate::policy(MaintenanceTicket::class, MaintenanceTicketPolicy::class);

        Reservation::observe(OperationalNotificationObserver::class);
        CleaningTask::observe(OperationalNotificationObserver::class);
        MaintenanceTicket::observe(OperationalNotificationObserver::class);
        Payment::observe(OperationalNotificationObserver::class);

        Property::observe(AuditLogObserver::class);
        Room::observe(AuditLogObserver::class);
        Reservation::observe(AuditLogObserver::class);
        Payment::observe(AuditLogObserver::class);
        ReservationService::observe(AuditLogObserver::class);
        CheckIn::observe(AuditLogObserver::class);
        CheckOut::observe(AuditLogObserver::class);
        CleaningTask::observe(AuditLogObserver::class);
        MaintenanceTicket::observe(AuditLogObserver::class);
        AvailabilityBlock::observe(AuditLogObserver::class);

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by(
            strtolower((string) $request->input('email')).'|'.$request->ip()
        ));

        RateLimiter::for('public-booking', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
    }
}
