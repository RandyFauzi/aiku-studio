<?php

return [
    'hourly_rate' => (int) env('PAYROLL_HOURLY_RATE', 50000),
    'timezone' => 'Asia/Makassar',
    'account_domain' => env('STUDIO_ACCOUNT_DOMAIN', 'kamerakitaid.site'),
    'atlas_url' => env('ATLAS_URL', 'https://atlasdashboard.vtlabs.dev'),
    'atlas_cookie' => env('ATLAS_COOKIE', ''),
    'atlas_timeout' => 12,
    'atlas_max_pages' => 200,
];
