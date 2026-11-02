<?php

use App\Services\PayrollCalculator;
use App\Services\PayrollPeriod;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$oracles = json_decode(file_get_contents(storage_path('app/private/migration/legacy-payroll-oracle.json')), true, 512, JSON_THROW_ON_ERROR);
$report = [];
$failures = [];
foreach ($oracles as $oracle) {
    $period = app(PayrollPeriod::class)->resolve(['periodId' => $oracle['period']['id']]);
    $actual = app(PayrollCalculator::class)->summary($period);
    foreach ($oracle['stats'] as $field => $value) {
        if (abs((float) $value - (float) $actual['stats'][$field]) > 0.001) {
            $failures[] = $period['id'].' stats '.$field;
        }
    }
    $workers = collect($actual['workers'])->keyBy('worker_name');
    foreach ($oracle['workers'] as $worker) {
        $target = $workers->get($worker['worker_name']);
        if (! $target) {
            $failures[] = $period['id'].' missing worker';

            continue;
        }
        foreach (['total_tasks', 'approved_min', 'approved_hours', 'rejected_min', 'pending_min', 'logged_hours', 'total_duration_min', 'base_thp', 'bonus', 'thp', 'work_days_count', 'status', 'slip_id', 'has_slip'] as $field) {
            $equal = is_numeric($worker[$field]) ? abs((float) $worker[$field] - (float) $target[$field]) < 0.001 : $worker[$field] === $target[$field];
            if (! $equal) {
                $failures[] = $period['id'].' worker '.$worker['worker_name'].' field '.$field;
            }
        }
    }
    $report[] = ['period' => $period['id'], 'workers' => count($actual['workers']), 'thp' => $actual['stats']['totalThp']];
}
file_put_contents(storage_path('app/private/migration/payroll-parity.json'), json_encode(['periods' => $report, 'errors' => $failures], JSON_PRETTY_PRINT));
foreach ($report as $result) {
    echo $result['period'].': '.$result['workers'].' workers, THP '.$result['thp'].PHP_EOL;
}
if ($failures) {
    echo implode(PHP_EOL, $failures).PHP_EOL;
    exit(1);
}
echo 'All payroll fields match the original Node.js handler.'.PHP_EOL;
