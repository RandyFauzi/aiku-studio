<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use App\Models\User;
use App\Services\ContributorAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContributorController extends Controller
{
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Hanya akun admin yang dapat mengelola akun dan mengubah role.');
    }

    public function index(Request $request)
    {
        $this->ensureAdmin($request);
        $users = User::whereNotNull('contributor_id')->get()->keyBy('contributor_id');

        return Contributor::orderBy('nama')->get()->map(function ($person) use ($users) {
            $user = $users->get($person->id);
            $role = $user?->role ?? $person->role ?? 'contributor';
            $isActive = (bool) ($user && $user->is_active);

            return $person->setAttribute('login_enabled', $isActive)
                ->setAttribute('role', $role)
                ->setAttribute('user_id', $user?->id);
        });
    }

    private function fields(Request $request): array
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'no_wa' => 'nullable|string|max:255',
            'domisili' => 'nullable|string|max:255',
            'no_rek' => 'nullable|string|max:255',
            'shift' => 'nullable|string|max:255',
            'hp' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'role' => 'nullable|string|in:admin,contributor',
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureAdmin($request);

        return DB::transaction(function () use ($request) {
            $fields = $this->fields($request);
            $role = $fields['role'] ?? 'contributor';
            $person = Contributor::create(array_merge($fields, ['role' => $role]));
            app(ContributorAccess::class)->sync($person, $role);

            $person->setAttribute('login_enabled', (bool) $person->email);
            $person->setAttribute('role', $role);

            return $person;
        });
    }

    public function update(Request $request, Contributor $contributor)
    {
        $this->ensureAdmin($request);

        return DB::transaction(function () use ($request, $contributor) {
            $fields = $this->fields($request);
            $user = User::where('contributor_id', $contributor->id)->first();
            $targetRole = $fields['role'] ?? $contributor->role ?? $user?->role ?? 'contributor';

            if ($user && $request->user()?->id === $user->id && $targetRole !== 'admin') {
                throw ValidationException::withMessages(['role' => 'Anda tidak dapat mengubah role akun Anda sendiri menjadi Pekerja.']);
            }

            $contributor->update(array_merge($fields, ['role' => $targetRole]));
            app(ContributorAccess::class)->sync($contributor, $targetRole);

            $user = User::where('contributor_id', $contributor->id)->first();
            $contributor->setAttribute('login_enabled', (bool) ($user && $user->is_active));
            $contributor->setAttribute('role', $targetRole);
            $contributor->setAttribute('user_id', $user?->id);

            return $contributor;
        });
    }

    public function updateRole(Request $request, Contributor $contributor)
    {
        $this->ensureAdmin($request);
        $data = $request->validate([
            'role' => 'required|string|in:admin,contributor',
        ]);

        return DB::transaction(function () use ($request, $contributor, $data) {
            $user = User::where('contributor_id', $contributor->id)->first();
            if ($user && $request->user()?->id === $user->id && $data['role'] !== 'admin') {
                throw ValidationException::withMessages(['role' => 'Anda tidak dapat mengubah role akun Anda sendiri menjadi Pekerja.']);
            }

            $contributor->update(['role' => $data['role']]);
            app(ContributorAccess::class)->sync($contributor, $data['role']);

            $user = User::where('contributor_id', $contributor->id)->first();

            return response()->json([
                'success' => true,
                'id' => $contributor->id,
                'role' => $data['role'],
                'login_enabled' => (bool) ($user && $user->is_active),
            ]);
        });
    }

    public function destroy(Request $request, Contributor $contributor)
    {
        $this->ensureAdmin($request);
        DB::transaction(function () use ($contributor) {
            User::where('contributor_id', $contributor->id)->update(['is_active' => false]);
            $contributor->delete();
        });

        return response()->json(['success' => true]);
    }
}
