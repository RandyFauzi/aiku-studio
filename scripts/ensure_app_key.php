<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! config('app.key')) {
    Artisan::call('key:generate', ['--force' => true]);
    echo Artisan::output();
} else {
    echo 'Existing APP_KEY preserved.'.PHP_EOL;
}
