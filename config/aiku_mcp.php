<?php

return [
    'enabled' => (bool) env('AIKU_MCP_ENABLED', true),

    'token_prefix' => 'akm_',

    'rate_limit' => env('AIKU_MCP_RATE_LIMIT', '120,1'),

    'max_date_range_days' => (int) env('AIKU_MCP_MAX_DATE_RANGE_DAYS', 90),

    'default_limit' => 25,

    'max_limit' => 100,

    'timezone' => 'Asia/Makassar',

    'abilities_by_role' => [
        'admin' => [
            'context',
            'attendance',
            'operations',
            'payroll',
            'admin_ops',
            'system',
        ],
        'contributor' => [
            'context',
            'attendance',
            'operations',
            'payroll',
        ],
    ],

    'clients' => [
        'antigravity' => 'Antigravity',
        'claude_code' => 'Claude Code',
        'codex' => 'Codex CLI',
        'gemini_cli' => 'Gemini CLI',
        'custom' => 'Klien MCP Lainnya',
    ],
];
