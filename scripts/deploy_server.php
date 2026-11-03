<?php
/**
 * Automated Production Deployer for AIKu Studio on Hostinger
 * Fetches latest commit from GitHub main branch and updates production.
 */

declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);
set_time_limit(300);

$baseDir = '/home/u361393239/domains/aiku.appku.site';
$laravelRoot = $baseDir . '/aiku-studio';
$publicRoot = $baseDir . '/public_html';
$statusFile = $publicRoot . '/deploy_status.json';

$log = [];
$log[] = 'Deploy started at ' . date('c');

try {
    // 1. Download zip from GitHub
    $zipUrl = 'https://codeload.github.com/RandyFauzi/aiku-studio/zip/refs/heads/main';
    $tempZip = $baseDir . '/latest_deploy_' . time() . '.zip';
    
    $log[] = 'Downloading latest main archive from GitHub...';
    $context = stream_context_create([
        'http' => [
            'timeout' => 60,
            'user_agent' => 'AntigravityDeployer/1.0',
            'follow_location' => 1
        ]
    ]);
    
    $zipData = file_get_contents($zipUrl, false, $context);
    if ($zipData === false || strlen($zipData) < 1000) {
        throw new RuntimeException('Failed to download zip from GitHub or file is empty.');
    }
    file_put_contents($tempZip, $zipData);
    $log[] = 'Archive downloaded, size: ' . strlen($zipData) . ' bytes.';

    // 2. Open and extract zip
    $zip = new ZipArchive();
    if ($zip->open($tempZip) !== true) {
        throw new RuntimeException('Failed to open downloaded zip archive.');
    }

    $extractedCount = 0;
    $prefix = 'aiku-studio-main/';
    $prefixLen = strlen($prefix);

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entryName = $zip->getNameIndex($i);
        if (!str_starts_with($entryName, $prefix)) {
            continue;
        }

        $relPath = substr($entryName, $prefixLen);
        if ($relPath === '' || $relPath === false || str_contains($relPath, '..')) {
            continue;
        }

        // Never overwrite .env or deploy_status.json
        if ($relPath === '.env' || basename($relPath) === '.env' || $relPath === 'public/deploy_status.json') {
            continue;
        }

        $targetPath = $laravelRoot . '/' . $relPath;

        if (str_ends_with($entryName, '/')) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            continue;
        }

        $dirName = dirname($targetPath);
        if (!is_dir($dirName)) {
            mkdir($dirName, 0755, true);
        }

        $content = $zip->getFromIndex($i);
        if ($content === false) {
            continue;
        }

        file_put_contents($targetPath, $content);
        $extractedCount++;

        // If file is under public/, also sync to public_html (except index.php which has Hostinger-specific paths)
        if (str_starts_with($relPath, 'public/')) {
            $webRel = substr($relPath, strlen('public/'));
            if ($webRel === 'index.php') {
                continue;
            }
            $webTarget = $publicRoot . '/' . $webRel;
            $webDir = dirname($webTarget);
            if (!is_dir($webDir)) {
                mkdir($webDir, 0755, true);
            }
            file_put_contents($webTarget, $content);
        }
    }
    $zip->close();
    @unlink($tempZip);
    $log[] = "Extracted {$extractedCount} files to {$laravelRoot} and synced public assets.";

    // 3. Invalidate opcache if available
    if (function_exists('opcache_reset')) {
        @opcache_reset();
        $log[] = 'Opcache reset.';
    }

    // 4. Bootstrap Laravel
    $log[] = 'Bootstrapping Laravel...';
    require $laravelRoot . '/vendor/autoload.php';
    $app = require $laravelRoot . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // 5. Run database migrations
    $log[] = 'Running database migrations...';
    Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $migrateLog = trim(Illuminate\Support\Facades\Artisan::output());
    $log[] = 'Migration output: ' . ($migrateLog ?: 'Nothing to migrate.');

    // 6. Sync contributor roles and accounts
    $log[] = 'Syncing contributor roles...';
    $synced = 0;
    $access = new App\Services\ContributorAccess();
    foreach (App\Models\Contributor::all() as $contributor) {
        if ($access->sync($contributor)) {
            $synced++;
        }
    }
    $log[] = "Synced {$synced} contributors.";

    // 7. Clear view & route caches
    Illuminate\Support\Facades\Artisan::call('view:clear');
    $log[] = 'View cache cleared: ' . trim(Illuminate\Support\Facades\Artisan::output());

    Illuminate\Support\Facades\Artisan::call('route:cache');
    $log[] = 'Route cache rebuilt: ' . trim(Illuminate\Support\Facades\Artisan::output());

    $success = true;
    $message = 'Deployment completed successfully!';
} catch (Throwable $e) {
    $success = false;
    $message = 'Deployment failed: ' . $e->getMessage();
    $log[] = 'ERROR: ' . $e->getMessage() . "\n" . $e->getTraceAsString();
}

$result = [
    'ok' => $success,
    'message' => $message,
    'timestamp' => date('c'),
    'log' => $log,
];

file_put_contents($statusFile, json_encode($result, JSON_PRETTY_PRINT));
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;

// Self delete the deployer script after execution
@unlink(__FILE__);
