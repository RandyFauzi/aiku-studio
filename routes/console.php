<?php

use App\Models\User;
use App\Services\RecordStreak;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// One short PHP operation per minute. No persistent worker or shell process.
Schedule::call(fn () => Artisan::call('atlas:tick'))->name('atlas-sync-tick')->everyMinute()->withoutOverlapping(3);

// Automatically synchronize 1618 Robotics recordings every 15 minutes without overloading server.
Schedule::command('1618:sync')->name('1618-sync-schedule')->everyFifteenMinutes()->withoutOverlapping(10);

Artisan::command('streak:reconcile', function () {
    User::where('role', 'contributor')->where('is_active', true)->whereNotNull('contributor_id')
        ->chunkById(100, function ($users) {
            foreach ($users as $user) {
                app(RecordStreak::class)->summary($user);
            }
        });
})->purpose('Refresh Daily Record streaks using WITA workdays');

Schedule::command('streak:reconcile')->hourly()->withoutOverlapping(30);
