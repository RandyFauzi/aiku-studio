<?php

return [
    'hourly_rate' => (int) env('PAYROLL_HOURLY_RATE', 50000),
    'timezone' => 'Asia/Makassar',
    'account_domain' => env('STUDIO_ACCOUNT_DOMAIN', 'kamerakitaid.site'),
    'atlas_url' => env('ATLAS_URL', 'https://atlasdashboard.vtlabs.dev'),
    'atlas_cookie' => env('ATLAS_COOKIE', ''),
    'atlas_timeout' => 12,
    'atlas_max_pages' => 200,

    // 1618 Robotics Integration
    'robotics_1618_url' => env('ROBOTICS_1618_BASE_URL', 'https://partners.1618robotics.com'),
    'robotics_1618_tenant' => env('ROBOTICS_1618_TENANT', 'kamerakita-ai'),
    'robotics_1618_username' => env('ROBOTICS_1618_USERNAME', 'kamerakita-ai'),
    'robotics_1618_password' => env('ROBOTICS_1618_PASSWORD', 'RrtVZnLYuRfKYTkvtaUU'),
    'robotics_1618_timeout' => (int) env('ROBOTICS_1618_TIMEOUT', 60),
];
