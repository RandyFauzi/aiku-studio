<?php

namespace App\Http\Controllers;

use App\Models\ShiftSetting;
use App\Models\StudioRecording;
use App\Services\AtlasSync;
use App\Services\Robotics1618Sync;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function requestSync(AtlasSync $sync, Robotics1618Sync $sync1618)
    {
        try {
            // First trigger 1618 sync if credentials are configured
            $result1618 = null;
            if (config('payroll.robotics_1618_username') && config('payroll.robotics_1618_password')) {
                try {
                    $result1618 = $sync1618->sync();
                } catch (\Throwable $e) {
                    // Log and continue to Atlas
                    report($e);
                }
            }

            // Also trigger Atlas sync if cookie exists
            if ($sync->cookie() !== '') {
                $atlasRes = $sync->request();
                return response()->json([
                    'status' => 'started',
                    'message' => 'Sinkronisasi rekaman berhasil diproses.',
                    'result_1618' => $result1618,
                    'result_atlas' => $atlasRes,
                ]);
            }

            if ($result1618) {
                return response()->json($result1618);
            }

            return response()->json(['status' => 'completed', 'message' => 'Sinkronisasi selesai.']);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function sync1618(Robotics1618Sync $sync)
    {
        try {
            return response()->json($sync->sync());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
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
