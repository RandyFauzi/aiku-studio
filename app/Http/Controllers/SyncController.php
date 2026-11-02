<?php

namespace App\Http\Controllers;

use App\Models\ShiftSetting;
use App\Models\StudioRecording;
use App\Services\AtlasSync;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function requestSync(AtlasSync $sync)
    {
        try {
            return response()->json($sync->request());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function status(AtlasSync $sync)
    {
        $state = $sync->state();

        return response()->json(['isRunning' => $state['isRunning'] ?? false, 'progress' => $state,
            'liveOutput' => implode("\n", $state['recent_logs'] ?? []),
            'lastResult' => empty($state['completedAt']) && empty($state['error']) ? null : ['timestamp' => $state['updatedAt'], 'exitCode' => empty($state['error']) ? 0 : 1, 'output' => $state['message'], 'error' => $state['error'] ?? ''],
            'lastSyncedAt' => StudioRecording::max('last_synced_at'), 'autoSync' => $sync->auto()]);
    }

    public function toggle(Request $request, AtlasSync $sync)
    {
        $data = $request->validate(['enabled' => 'sometimes|boolean', 'intervalMinutes' => 'sometimes|integer|min:5|max:1440']);
        $auto = $sync->auto();
        $auto['enabled'] = $data['enabled'] ?? ! $auto['enabled'];
        $auto['intervalMinutes'] = $data['intervalMinutes'] ?? $auto['intervalMinutes'];
        $auto['nextRun'] = $auto['enabled'] ? now()->addMinutes($auto['intervalMinutes'])->toIso8601String() : null;
        ShiftSetting::updateOrCreate(['setting_key' => 'atlas_auto_sync'], ['setting_value' => $auto]);

        return response()->json(['success' => true, 'autoSync' => $auto]);
    }
}
