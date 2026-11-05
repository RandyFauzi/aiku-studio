<?php

namespace App\Services;

use App\Models\Contributor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ContributorIdentity
{
    public function resolve(User $user): Contributor
    {
        abort_unless($user->role === 'contributor', 403);

        return DB::transaction(function () use ($user) {
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($account->contributor_id !== null) {
                // A deleted identity must not silently become a different person.
                return Contributor::find($account->contributor_id) ?? abort(403, 'Data kontributor tidak tersedia. Hubungi admin.');
            }
            $email = strtolower(trim($account->email));
            $matches = Contributor::whereRaw('LOWER(TRIM(email)) = ?', [$email])->get();
            abort_if($matches->count() > 1, 403, 'Email kontributor tidak unik. Hubungi admin.');
            $person = $matches->first();
            if ($person) {
                abort_if(User::where('contributor_id', $person->id)->whereKeyNot($account->id)->exists(), 403, 'Kontributor sudah terhubung ke akun lain.');
            } else {
                abort_if(Contributor::whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower(trim($account->name))])->exists(), 403, 'Nama cocok dengan akun lain tetapi email berbeda. Hubungi admin.');
                $person = Contributor::create(['nama' => $account->name, 'email' => $email, 'role' => 'contributor']);
            }
            $account->forceFill(['contributor_id' => $person->id])->save();
            $user->contributor_id = $person->id;

            return $person;
        });
    }

    public function payrollName(User $user): string
    {
        $person = $this->resolve($user);
        // Legacy payroll joins by name. Fail closed when two people share a name.
        abort_unless(Contributor::whereRaw('LOWER(TRIM(nama)) = ?', [mb_strtolower(trim($person->nama))])->count() === 1, 403, 'Nama payroll tidak unik. Hubungi admin.');

        return $person->nama;
    }
}
