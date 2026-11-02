<?php

namespace App\Http\Controllers;

use App\Models\Contributor;
use Illuminate\Http\Request;

class ContributorController extends Controller
{
    public function index()
    {
        return Contributor::orderBy('nama')->get();
    }

    private function fields(Request $request): array
    {
        return $request->validate(['nama' => 'required|string|max:255', 'no_wa' => 'nullable|string|max:255',
            'domisili' => 'nullable|string|max:255', 'no_rek' => 'nullable|string|max:255', 'shift' => 'nullable|string|max:255',
            'hp' => 'nullable|string|max:255', 'email' => 'nullable|email|max:255']);
    }

    public function store(Request $request)
    {
        return Contributor::create($this->fields($request));
    }

    public function update(Request $request, Contributor $contributor)
    {
        $contributor->update($this->fields($request));

        return $contributor;
    }

    public function destroy(Contributor $contributor)
    {
        $contributor->delete();

        return response()->json(['success' => true]);
    }
}
