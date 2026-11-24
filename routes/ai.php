<?php

use App\Http\Middleware\AuditAikuMcp;
use App\Http\Middleware\AuthenticateAikuMcp;
use App\Mcp\Servers\AikuServer;
use Laravel\Mcp\Facades\Mcp;

$rateLimit = (string) config('aiku_mcp.rate_limit', '120,1');

Mcp::web('/mcp/aiku', AikuServer::class)
    ->middleware([
        AuditAikuMcp::class,
        "throttle:{$rateLimit}",
        AuthenticateAikuMcp::class,
    ]);

Mcp::local('aiku', AikuServer::class);
