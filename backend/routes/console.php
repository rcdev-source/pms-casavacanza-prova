<?php

use App\Models\Notification;
use App\Models\PreCheckInToken;
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::call(
    fn () => PreCheckInToken::query()
        ->where('expires_at', '<', now()->subDays(30))
        ->delete()
)->dailyAt('02:15')->name('prune-expired-pre-check-in-tokens')->withoutOverlapping();
Schedule::call(
    fn () => Notification::query()
        ->whereNotNull('read_at')
        ->where('read_at', '<', now()->subDays(90))
        ->delete()
)->dailyAt('02:30')->name('prune-read-notifications')->withoutOverlapping();
