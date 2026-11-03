<?php
$indexContent = <<<'PHP'
<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));
$applicationPath = dirname(__DIR__).'/aiku-studio';

if (file_exists($maintenance = $applicationPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $applicationPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $applicationPath.'/bootstrap/app.php';
$app->usePublicPath(__DIR__);
$app->handleRequest(Request::capture());
PHP;

file_put_contents(__DIR__ . '/index.php', $indexContent);
echo "INDEX_RESTORED";
@unlink(__FILE__);
