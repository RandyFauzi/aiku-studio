<?php

namespace App\Http\Controllers;

use App\Models\DailyWorkReport;
use App\Services\ContributorIdentity;
use App\Services\RecordStreak;
use Illuminate\Http\Request;

class RecordStreakController extends Controller
{
    public function mine(Request $request, RecordStreak $streak)
    {
        app(ContributorIdentity::class)->resolve($request->user());

        return response()->json(['streak' => $streak->summary($request->user())]);
    }

    public function celebrate(Request $request, RecordStreak $streak)
    {
        app(ContributorIdentity::class)->resolve($request->user());

        return response()->json($streak->celebrate($request->user()));
    }

    public function verify(Request $request, DailyWorkReport $report, RecordStreak $streak)
    {
        abort_unless($request->user()->role === 'admin', 403);
        $data = $request->validate(['expected_revision' => 'required|integer|min:1']);

        return response()->json(['success' => true, 'streak' => $streak->verify($report, $request->user(), $data['expected_revision'])]);
    }
}
