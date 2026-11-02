<?php

use App\Models\StudioAccount;
use App\Services\AtlasParser;
use App\Services\AtlasSync;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$sync = app(AtlasSync::class);
$cookie = $sync->cookie();
echo 'Accounts: '.StudioAccount::count().'; missing participant IDs: '.StudioAccount::whereNull('participant_id')->count().PHP_EOL;
if ($cookie === '') {
    echo 'Atlas session unavailable or expired; refresh required.'.PHP_EOL;
    exit(2);
}
$account = StudioAccount::where('active', true)->whereNotNull('participant_id')->firstOrFail();
try {
    $response = Http::timeout(25)->withHeaders(['Cookie' => $cookie])->withOptions(['allow_redirects' => false])
        ->get(rtrim(config('payroll.atlas_url'), '/').'/recordings', ['page' => 1, 'sort' => 'recordedAt', 'direction' => 'desc', 'participantId' => $account->participant_id]);
} catch (ConnectionException $e) {
    echo 'Atlas network probe failed: DNS/connectivity unavailable. No data changed.'.PHP_EOL;
    exit(2);
}
echo 'Atlas read-only probe HTTP: '.$response->status().PHP_EOL;
if (! $response->successful()) {
    echo 'Session or network must be checked before live sync.'.PHP_EOL;
    exit(2);
}
$data = app(AtlasParser::class)->recordings($response->body());
echo 'Atlas parser verified on live response: '.count($data['recordings']).' recordings on first page. No records written.'.PHP_EOL;
