<?php

namespace App\Services;

use App\Models\ShiftSetting;
use App\Models\StudioAccount;
use App\Models\StudioRecording;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class AtlasSync
{
    public function state(): array
    {
        return ShiftSetting::where('setting_key', 'atlas_sync_state')->value('setting_value') ?? ['isRunning' => false, 'percent' => 0, 'message' => 'Belum ada sinkronisasi Laravel.', 'recent_logs' => []];
    }

    public function auto(): array
    {
        return ShiftSetting::where('setting_key', 'atlas_auto_sync')->value('setting_value') ?? ['enabled' => false, 'intervalMinutes' => 15, 'lastRun' => null, 'nextRun' => null];
    }

    public function cookie(): string
    {
        if (config('payroll.atlas_cookie')) {
            return config('payroll.atlas_cookie');
        }
        $path = 'payroll/atlas-session.json';
        if (! Storage::disk('local')->exists($path)) {
            return '';
        }
        $state = json_decode(Storage::disk('local')->get($path), true);
        $host = parse_url(config('payroll.atlas_url'), PHP_URL_HOST);
        $cookies = [];
        foreach ($state['cookies'] ?? [] as $cookie) {
            $domain = ltrim($cookie['domain'] ?? '', '.');
            if (($host === $domain || str_ends_with($host, '.'.$domain)) && (($cookie['expires'] ?? -1) < 0 || $cookie['expires'] > time())) {
                $cookies[] = $cookie['name'].'='.$cookie['value'];
            }
        }

        return implode('; ', $cookies);
    }

    public function request(): array
    {
        return Cache::lock('atlas-sync-state', 120)->block(3, function () {
            $state = $this->state();
            if ($state['isRunning'] ?? false) {
                return ['status' => 'running', 'message' => $state['message']];
            }
            if ($this->cookie() === '') {
                throw new \RuntimeException('Sesi Atlas belum tersedia atau kedaluwarsa. Perbarui ATLAS_COOKIE atau file sesi privat.');
            }
            $ids = StudioAccount::where('active', true)->orderBy('id')->pluck('id')->all();
            if (! $ids) {
                throw new \RuntimeException('Tidak ada akun studio aktif.');
            }
            $this->save(['isRunning' => true, 'stage' => 'queued', 'accountIds' => $ids, 'currentIndex' => 0, 'totalAccounts' => count($ids),
                'page' => 1, 'percent' => 0, 'recordsCount' => 0, 'message' => 'Menunggu cron Laravel. Sinkronisasi diproses bertahap.', 'recent_logs' => [], 'startedAt' => now()->toIso8601String()]);
            $auto = $this->auto();
            $auto['lastRun'] = now()->toIso8601String();
            $auto['nextRun'] = $auto['enabled'] ? now()->addMinutes($auto['intervalMinutes'])->toIso8601String() : null;
            ShiftSetting::updateOrCreate(['setting_key' => 'atlas_auto_sync'], ['setting_value' => $auto]);

            return ['status' => 'started', 'message' => 'Sinkronisasi masuk antrean cron server.'];
        });
    }

    public function tick(): void
    {
        Cache::lock('atlas-sync-state', 120)->get(function () {
            $state = $this->state();
            if (! ($state['isRunning'] ?? false)) {
                return;
            }
            try {
                $index = $state['currentIndex'];
                $account = StudioAccount::find($state['accountIds'][$index] ?? null);
                if (! $account || ! $account->active) {
                    $this->advance($state);

                    return;
                }
                if (! $account->participant_id) {
                    $html = $this->fetch('/recordings');
                    foreach (app(AtlasParser::class)->participants($html) as $email => $id) {
                        StudioAccount::where('email', $email)->update(['participant_id' => $id]);
                    }
                    $account->refresh();
                    if (! $account->participant_id) {
                        $state['warnings'][] = 'ID peserta belum ditemukan untuk '.$account->email.'.';
                        $state['recent_logs'][] = end($state['warnings']);
                        $this->advance($state);

                        return;
                    }
                }
                $page = $state['page'];
                $html = $this->fetch('/recordings?'.http_build_query(['page' => $page, 'sort' => 'recordedAt', 'direction' => 'desc', 'participantId' => $account->participant_id]));
                $props = app(AtlasParser::class)->recordings($html);
                $records = $props['recordings'];
                foreach ($records as $record) {
                    $this->ingest($record, $account->email);
                }
                $state['recordsCount'] += count($records);
                $state['currentAccount'] = $account->email;
                $state['stage'] = 'scraping';
                $state['message'] = 'Memproses akun '.($index + 1).'/'.$state['totalAccounts'].', halaman '.$page.'.';
                $state['recent_logs'][] = now()->format('H:i:s').' '.$state['message'];
                $state['recent_logs'] = array_slice($state['recent_logs'], -40);
                $hasRecentOrPending = collect($records)->contains(fn ($r) => (float) ($r['tilePendingHours'] ?? 0) > 0 || (! empty($r['recordedAt']) && CarbonImmutable::parse($r['recordedAt'])->gte(CarbonImmutable::now()->subDays(38))));
                $totalPages = max(1, (int) ceil(($props['total'] ?? count($records)) / 25));
                if ($records && $page < $totalPages && $hasRecentOrPending) {
                    if ($page >= config('payroll.atlas_max_pages')) {
                        throw new \RuntimeException('Batas halaman sinkronisasi tercapai. Periksa akun atau naikkan batas konfigurasi.');
                    }
                    $state['page']++;
                    $state['percent'] = (int) floor($index / $state['totalAccounts'] * 100);
                    $this->save($state);
                } else {
                    $this->advance($state);
                }
            } catch (\Throwable $e) {
                report($e);
                $state['isRunning'] = false;
                $state['stage'] = 'failed';
                $state['message'] = $e->getMessage();
                $state['error'] = $e->getMessage();
                $this->save($state);
            }
        });
    }

    private function fetch(string $path): string
    {
        $cookie = $this->cookie();
        if (! $cookie) {
            throw new \RuntimeException('Sesi Atlas kedaluwarsa. Perbarui sesi privat.');
        }
        $response = Http::timeout(config('payroll.atlas_timeout'))->withHeaders(['Cookie' => $cookie, 'Accept' => 'text/html'])
            ->withOptions(['allow_redirects' => false])->get(rtrim(config('payroll.atlas_url'), '/').$path);
        if (! $response->successful()) {
            throw new \RuntimeException('Atlas mengembalikan HTTP '.$response->status().'; periksa sesi dan koneksi.');
        }

        return $response->body();
    }

    public function ingest(array $r, string $email): void
    {
        $id = strtolower(trim((string) ($r['userTaskId'] ?? '')));
        if (! $id || strlen($id) > 64 || empty($r['recordedAt'])) {
            throw new \RuntimeException('Rekaman Atlas tidak memiliki ID/tanggal yang valid.');
        }
        $dt = CarbonImmutable::parse($r['recordedAt'])->utc();
        $duration = (float) ($r['durationSeconds'] ?? 0);
        $billable = (float) ($r['tileBillableHours'] ?? 0);
        $rejected = (float) ($r['tileRejectedHours'] ?? 0);
        $values = ['participant_email' => strtolower($email), 'task_title' => $r['taskTitle'] ?? '', 'recorded_at' => $dt->format('Y-m-d H:i:s'),
            'recorded_utc' => $dt->setTimezone('Asia/Makassar')->format('M j, Y, g:i A').' WITA',
            'duration_sec' => $duration, 'duration_min' => round($duration / 60, 2), 'processing_status' => $r['processingStatus'] ?? '',
            'billable_status' => ($r['billable'] ?? null) === true ? 'yes' : ((($r['billable'] ?? null) === false && $rejected > 0) ? 'no' : null),
            'billable_min' => round($billable * 60, 2), 'rejected_min' => round($rejected * 60, 2),
            'pending_min' => round((float) ($r['tilePendingHours'] ?? 0) * 60, 2), 'deferred_min' => round((float) ($r['tileDeferredHours'] ?? 0) * 60, 2),
            'reviewed_utc' => ! empty($r['reviewedAt']) ? CarbonImmutable::parse($r['reviewedAt'])->setTimezone('Asia/Makassar')->format('M j, Y, g:i A').' WITA' : null,
            'reconciliation' => $r['reconciliationStatus'] ?? '', 'last_synced_at' => now()];
        // Only source-controlled fields change. Manual assignments, notes and payment statuses survive every refresh.
        StudioRecording::updateOrCreate(['record_id' => $id], $values);
    }

    private function advance(array $state): void
    {
        $state['currentIndex']++;
        $state['page'] = 1;
        $state['percent'] = (int) floor($state['currentIndex'] / $state['totalAccounts'] * 100);
        if ($state['currentIndex'] >= $state['totalAccounts']) {
            $state['isRunning'] = false;
            $state['stage'] = 'completed';
            $state['message'] = 'Sinkronisasi selesai: '.$state['recordsCount'].' rekaman diperiksa.';
            $state['completedAt'] = now()->toIso8601String();
            $auto = $this->auto();
            $auto['lastRun'] = $state['completedAt'];
            $auto['nextRun'] = $auto['enabled'] ? now()->addMinutes($auto['intervalMinutes'])->toIso8601String() : null;
            ShiftSetting::updateOrCreate(['setting_key' => 'atlas_auto_sync'], ['setting_value' => $auto]);
        }
        $this->save($state);
    }

    private function save(array $state): void
    {
        $state['updatedAt'] = now()->toIso8601String();
        ShiftSetting::updateOrCreate(['setting_key' => 'atlas_sync_state'], ['setting_value' => $state]);
    }
}
