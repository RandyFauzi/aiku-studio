<?php

namespace App\Http\Controllers;

use App\Models\ShiftSetting;
use App\Models\StudioRecording;
use App\Services\AtlasSync;
use App\Services\Robotics1618Sync;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function requestSync(\App\Services\RecordingSync $sync)
    {
        try {
            return response()->json($sync->request(), 202);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
            return response()->json(['error' => 'Bot sedang memproses permintaan lain. Periksa progres beberapa saat lagi.'], 409);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => 'failed', 'error' => $e->getMessage()], 422);
        }
    }

    public function sync1618(Robotics1618Sync $sync)
    {
        try {
            return response()->json($sync->sync());
        } catch (\Throwable $e) {
            return response()->json(['status' => 'failed', 'error' => $e->getMessage()], 422);
        }
    }

    public function status1618(Robotics1618Sync $sync)
    {
        $state = $sync->state();

        return response()->json([
            'isRunning' => $state['isRunning'] ?? false,
            'state' => $state,
            'lastSyncedAt' => StudioRecording::max('last_synced_at'),
        ]);
    }

    public function status(\App\Services\RecordingSync $sync, AtlasSync $atlas)
    {
        $state = $sync->state();

        return response()->json(['isRunning' => $state['isRunning'] ?? false, 'progress' => $state,
            'liveOutput' => implode("\n", $state['recent_logs'] ?? []),
            'lastResult' => empty($state['completedAt']) && empty($state['error']) ? null : ['timestamp' => $state['updatedAt'], 'exitCode' => empty($state['error']) ? 0 : 1, 'output' => $state['message'], 'error' => $state['error'] ?? ''],
            'lastSyncedAt' => StudioRecording::max('last_synced_at'), 'autoSync' => $atlas->auto()]);
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
