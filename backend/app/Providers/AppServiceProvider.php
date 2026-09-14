<?php

namespace App\Providers;

use App\Models\Guest;
use App\Models\Property;
use App\Models\Reservation;
use App\Models\Room;
use App\Policies\GuestPolicy;
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

        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(5)->by(
            strtolower((string) $request->input('email')).'|'.$request->ip()
        ));

        RateLimiter::for('public-booking', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
    }
}
