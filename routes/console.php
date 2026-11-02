<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// One short PHP operation per minute. No persistent worker or shell process.
Schedule::call(fn () => Artisan::call('atlas:tick'))->name('atlas-sync-tick')->everyMinute()->withoutOverlapping(3);
