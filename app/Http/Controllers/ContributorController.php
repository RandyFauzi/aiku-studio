<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use App\Models\User;
use App\Services\ContributorAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContributorController extends Controller
{
    public function index()
    {
        $enabled = User::where('role', 'contributor')->where('is_active', true)->pluck('contributor_id')->all();

        return Contributor::orderBy('nama')->get()->map(fn ($person) => $person->setAttribute('login_enabled', in_array($person->id, $enabled)));
    }

    private function fields(Request $request): array
    {
        return $request->validate(['nama' => 'required|string|max:255', 'no_wa' => 'nullable|string|max:255',
            'domisili' => 'nullable|string|max:255', 'no_rek' => 'nullable|string|max:255', 'shift' => 'nullable|string|max:255',
            'hp' => 'nullable|string|max:255', 'email' => 'nullable|email|max:255']);
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $person = Contributor::create($this->fields($request));
            app(ContributorAccess::class)->sync($person);

            return $person;
        });
    }

    public function update(Request $request, Contributor $contributor)
    {
        return DB::transaction(function () use ($request, $contributor) {
            $contributor->update($this->fields($request));
            app(ContributorAccess::class)->sync($contributor);

            return $contributor;
        });
    }

    public function destroy(Contributor $contributor)
    {
        DB::transaction(function () use ($contributor) {
            User::where('contributor_id', $contributor->id)->update(['is_active' => false]);
            $contributor->delete();
        });

        return response()->json(['success' => true]);
    }
}
