<?php

namespace App\Services;

use App\Models\ShiftSetting;
use Illuminate\Support\Facades\Cache;

/** Coordinates both sources without doing remote work in a web request. */
class RecordingSync
{
    public function __construct(private AtlasSync $atlas, private Robotics1618Sync $robotics) {}

    public function state(): array
    {
        $state = ShiftSetting::where('setting_key', 'recording_sync_state')->value('setting_value')
            ?? ['isRunning' => false, 'stage' => 'idle', 'sources' => [], 'message' => 'Bot siap dijalankan.'];
        foreach ($state['sources'] as $key => &$source) {
            if (($source['stage'] ?? '') === 'running') {
                $live = $key === 'atlas' ? $this->atlas->state() : $this->robotics->state();
                $source = array_merge($source, $live);
            }
        }
        unset($source);
        $sources = $state['sources'];
        $state['percent'] = $sources ? (int) floor(array_sum(array_map(fn ($s) => (float) ($s['percent'] ?? 0), $sources)) / count($sources)) : 0;
        $state['recordsCount'] = array_sum(array_map(fn ($s) => $s['recordsCount'] ?? $s['matchedStudioRows'] ?? 0, $sources));
        $state['recent_logs'] = [];
        foreach ($sources as $key => $source) {
            foreach ($source['recent_logs'] ?? [$source['message'] ?? ''] as $log) {
                $state['recent_logs'][] = '['.($key === 'atlas' ? 'Atlas' : '1618').'] '.$log;
            }
        }
        if (($state['isRunning'] ?? false) && $this->stale($state)) {
            $state['stage'] = 'error';
            $state['error'] = $state['message'] = 'Bot tidak memperbarui progres lebih dari 10 menit. Periksa cron server, lalu klik Jalankan Ulang.';
            $state['isRunning'] = false;
        }

        return $state;
    }

    public function request(): array
    {
        return Cache::lock('recording-sync', 300)->block(3, function () {
            $current = $this->state();
            if ($current['isRunning']) {
                return ['status' => 'running', 'message' => 'Sinkronisasi kedua sumber sedang berjalan.'];
            }
            $oldAtlas = $this->atlas->state();
            if (($oldAtlas['isRunning'] ?? false) && $this->stale($oldAtlas)) {
                $this->atlas->fail('Proses Atlas sebelumnya terhenti; memulai ulang.');
            }
            $sources = [];
            try {
                $this->atlas->request();
                $sources['atlas'] = $this->atlas->state();
                $sources['atlas']['stage'] = 'queued';
            } catch (\Throwable $e) {
                report($e);
                $sources['atlas'] = $this->failure('Atlas: '.$e->getMessage());
            }
            $sources['robotics'] = config('payroll.robotics_1618_username') && config('payroll.robotics_1618_password')
                ? ['stage' => 'queued', 'percent' => 0, 'message' => 'Menunggu cron server.']
                : $this->failure('1618: kredensial belum dikonfigurasi. Hubungi admin server.');
            $state = ['isRunning' => true, 'stage' => 'queued', 'sources' => $sources,
                'startedAt' => now()->toIso8601String(), 'message' => 'Atlas dan 1618 masuk antrean cron server.'];
            $this->finishIfReady($state);
            $this->save($state);
            $this->scheduleNext();
            if (! $state['isRunning']) {
                throw new \RuntimeException($state['error']);
            }

            return ['status' => 'started', 'message' => $state['message']];
        });
    }

    public function tick(): void
    {
        $auto = $this->atlas->auto();
        $state = $this->state();
        if (! $state['isRunning'] && $auto['enabled'] && (! $auto['nextRun'] || now()->gte($auto['nextRun']))) {
            try {
                $this->request();
            } catch (\RuntimeException $e) {
                // The persisted source errors remain visible; retry at the next scheduled interval.
                report($e);
            }
        }
        Cache::lock('recording-sync', 300)->get(function () {
            $state = $this->state();
            if (! $state['isRunning']) {
                return;
            }
            // Work on one source per invocation to bound shared-hosting execution time.
            foreach (['robotics', 'atlas'] as $key) {
                if (! in_array($state['sources'][$key]['stage'], ['queued', 'running', 'scraping'], true)) {
                    continue;
                }
                $state['sources'][$key]['stage'] = 'running';
                $state['stage'] = 'running';
                $state['message'] = 'Memproses '.($key === 'atlas' ? 'Atlas' : '1618 Robotics').'...';
                $this->save($state);
                try {
                    if ($key === 'robotics') {
                        $this->robotics->sync();
                        $source = $this->robotics->state();
                    } else {
                        $started = microtime(true);
                        for ($page = 0; $page < 3; $page++) {
                            $this->atlas->tick();
                            if (! ($this->atlas->state()['isRunning'] ?? false) || microtime(true) - $started > 25) {
                                break;
                            }
                        }
                        $source = $this->atlas->state();
                    }
                    if ($source['isRunning'] ?? false) {
                        $source['stage'] = 'running';
                    }
                    $state['sources'][$key] = $source;
                } catch (\Throwable $e) {
                    report($e);
                    $state['sources'][$key] = $this->failure(($key === 'atlas' ? 'Atlas: ' : '1618: ').$e->getMessage());
                }
                break;
            }
            $this->finishIfReady($state);
            $this->save($state);
        });
    }

    private function finishIfReady(array &$state): void
    {
        foreach ($state['sources'] as $source) {
            if (in_array($source['stage'], ['queued', 'running', 'scraping'], true)) {
                return;
            }
        }
        $errors = [];
        foreach ($state['sources'] as $key => $source) {
            if ($source['stage'] !== 'completed') {
                $errors[] = ($key === 'atlas' ? 'Atlas: ' : '1618: ').($source['error'] ?? $source['message']);
            }
        }
        $state['isRunning'] = false;
        $state['stage'] = $errors ? 'error' : 'completed';
        $state['error'] = $errors ? implode(' | ', $errors) : null;
        $state['message'] = $errors ? 'Sinkronisasi belum lengkap. '.$state['error'] : 'Atlas dan 1618 selesai disinkronkan.';
        $state['completedAt'] = now()->toIso8601String();
        $this->scheduleNext();
    }

    private function failure(string $message): array
    {
        return ['isRunning' => false, 'stage' => 'failed', 'percent' => 0, 'error' => $message, 'message' => $message, 'recent_logs' => [$message]];
    }

    private function stale(array $state): bool
    {
        return ! empty($state['updatedAt']) && now()->subMinutes(10)->gt($state['updatedAt']);
    }

    private function scheduleNext(): void
    {
        $auto = $this->atlas->auto();
        $auto['lastRun'] = now()->toIso8601String();
        $auto['nextRun'] = $auto['enabled'] ? now()->addMinutes($auto['intervalMinutes'])->toIso8601String() : null;
        ShiftSetting::updateOrCreate(['setting_key' => 'atlas_auto_sync'], ['setting_value' => $auto]);
    }

    private function save(array $state): void
    {
        $state['updatedAt'] = now()->toIso8601String();
        ShiftSetting::updateOrCreate(['setting_key' => 'recording_sync_state'], ['setting_value' => $state]);
    }
}
