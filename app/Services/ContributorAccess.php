<?php

namespace App\Services;

use App\Models\Contributor;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ContributorAccess
{
    public function sync(Contributor $contributor, ?string $role = null): ?User
    {
        $user = User::where('contributor_id', $contributor->id)->first();
        $email = strtolower(trim($contributor->email ?? ''));
        if ($email === '') {
            $user?->forceFill(['is_active' => false])->save();

            return null;
        }
        $conflict = User::where('email', $email)->when($user, fn ($q) => $q->where('id', '!=', $user->id))->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['email' => 'Email ini sudah dipakai akun lain. Gunakan email berbeda untuk setiap kontributor.']);
        }
        $targetRole = $role ?? $contributor->role ?? $user?->role ?? 'contributor';
        if (! in_array($targetRole, ['admin', 'contributor'], true)) {
            $targetRole = 'contributor';
        }
        if ($contributor->role !== $targetRole) {
            $contributor->role = $targetRole;
            $contributor->saveQuietly();
        }
        $new = ! $user;
        $user ??= new User;
        $user->forceFill(['name' => $contributor->nama, 'email' => $email, 'contributor_id' => $contributor->id, 'role' => $targetRole, 'is_active' => true]);
        if ($new) {
            $user->password = 'password';
        }
        $user->save();

        return $user;
    }
}
