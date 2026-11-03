<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

try {
    $applicationPath = dirname(__DIR__) . '/aiku-studio';
    echo "App path: $applicationPath\n";
    echo "Autoload exists: " . (file_exists($applicationPath . '/vendor/autoload.php') ? 'yes' : 'no') . "\n";
    echo "Bootstrap exists: " . (file_exists($applicationPath . '/bootstrap/app.php') ? 'yes' : 'no') . "\n";
    echo ".env exists: " . (file_exists($applicationPath . '/.env') ? 'yes' : 'no') . "\n";
    
    require $applicationPath . '/vendor/autoload.php';
    echo "Autoload loaded\n";
    
    $app = require $applicationPath . '/bootstrap/app.php';
    echo "App bootstrapped\n";
    
    $app->usePublicPath(__DIR__);
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle(
        $request = Illuminate\Http\Request::create('/login.html', 'GET')
    );
    echo "Response status: " . $response->getStatusCode() . "\n";
    if ($response->getStatusCode() >= 400) {
        echo "Response content preview: " . substr($response->getContent(), 0, 500) . "\n";
    }
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
