<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class McpConnection extends Model
{
    use HasUuids;

    protected $table = 'mcp_connections';

    protected $fillable = [
        'user_id',
        'name',
        'client_type',
        'token_hash',
        'token_suffix',
        'token_encrypted',
        'account_version',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
        'token_encrypted',
    ];

    protected function casts(): array
    {
        return [
            'token_encrypted' => 'encrypted',
            'account_version' => 'integer',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function usableBy(?User $user = null): bool
    {
        $owner = $user ?? $this->user;

        if (! $owner instanceof User || ! $owner->is_active) {
            return false;
        }

        if ($this->isRevoked() || $this->isExpired() || $this->token_hash === null) {
            return false;
        }

        return (int) $this->account_version === (int) ($owner->account_version ?? 1);
    }

    /**
     * Issue a new personal MCP Bearer token for the given user.
     *
     * @return array{connection: self, plain_token: string}
     */
    public static function issueToken(
        User $user,
        string $name,
        string $clientType = 'custom',
        ?CarbonInterface $expiresAt = null,
    ): array {
        $prefix = (string) config('aiku_mcp.token_prefix', 'akm_');
        $plainToken = $prefix.Str::random(40);

        $connection = static::create([
            'user_id' => $user->id,
            'name' => $name,
            'client_type' => $clientType,
            'token_hash' => hash('sha256', $plainToken),
            'token_suffix' => substr($plainToken, -6),
            'token_encrypted' => $plainToken,
            'account_version' => (int) ($user->account_version ?? 1),
            'expires_at' => $expiresAt,
        ]);

        return [
            'connection' => $connection,
            'plain_token' => $plainToken,
        ];
    }

    public function revoke(): void
    {
        $this->forceFill([
            'token_hash' => null,
            'token_encrypted' => null,
            'revoked_at' => now(),
        ])->save();
    }
}
