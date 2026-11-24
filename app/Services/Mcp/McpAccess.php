<?php

namespace App\Services\Mcp;

use App\Models\Contributor;
use App\Models\User;
use App\Services\ContributorIdentity;
use Illuminate\Support\Facades\Auth;

class McpAccess
{
    public function __construct(
        private readonly ContributorIdentity $identity,
    ) {}

    public function currentUser(): ?User
    {
        $request = request();
        $user = $request?->attributes->get('mcp_user') ?? Auth::user();

        if (! $user instanceof User || ! $user->is_active) {
            return null;
        }

        return $user;
    }

    public function traceId(): string
    {
        $traceId = request()?->attributes->get('mcp_trace_id');

        return is_string($traceId) && $traceId !== '' ? $traceId : 'cli-local';
    }

    public function isAdmin(?User $user = null): bool
    {
        $actor = $user ?? $this->currentUser();

        return $actor instanceof User && $actor->role === 'admin' && $actor->is_active;
    }

    /**
     * @return list<string>
     */
    public function abilities(?User $user = null): array
    {
        $actor = $user ?? $this->currentUser();

        if (! $actor instanceof User || ! $actor->is_active) {
            return [];
        }

        $map = config('aiku_mcp.abilities_by_role', []);
        $abilities = $map[$actor->role] ?? [];

        return array_values(array_filter($abilities, 'is_string'));
    }

    public function allows(string $ability, ?User $user = null): bool
    {
        return in_array($ability, $this->abilities($user), true);
    }

    public function resolveContributor(User $user): ?Contributor
    {
        if ($user->role === 'contributor') {
            return $this->identity->resolve($user);
        }

        if ($user->contributor_id !== null) {
            return Contributor::find($user->contributor_id);
        }

        return null;
    }

    public function resolvePayrollName(User $user): string
    {
        return $this->identity->payrollName($user);
    }
}
