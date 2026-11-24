<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class McpToolCall extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'mcp_tool_calls';

    protected $fillable = [
        'trace_id',
        'mcp_connection_id',
        'user_id',
        'role',
        'method',
        'tool_name',
        'input_digest',
        'outcome',
        'http_status',
        'latency_ms',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'http_status' => 'integer',
            'latency_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(McpConnection::class, 'mcp_connection_id');
    }
}
