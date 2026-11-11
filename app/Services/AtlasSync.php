<?php

namespace App\Services;

use App\Models\ShiftSetting;
use App\Models\DeviceAccount;
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
            // Include every registered studio/device email, including historical and inactive accounts.
            foreach (DeviceAccount::pluck('email')->unique() as $email) {
                $email = strtolower(trim((string) $email));
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    StudioAccount::firstOrCreate(['email' => $email], ['name' => $email, 'active' => true]);
                }
            }
            $ids = StudioAccount::orderBy('id')->pluck('id')->all();
            if (! $ids) {
                throw new \RuntimeException('Tidak ada akun studio terdaftar.');
            }
            $this->save(['isRunning' => true, 'stage' => 'queued', 'accountIds' => $ids, 'currentIndex' => 0, 'totalAccounts' => count($ids),
                'page' => 1, 'percent' => 0, 'recordsCount' => 0, 'warnings' => [], 'skippedCount' => 0, 'message' => 'Menunggu cron Laravel. Sinkronisasi diproses bertahap.', 'recent_logs' => [], 'startedAt' => now()->toIso8601String()]);

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
                if (! $account) {
                    $this->advance($state);

                    return;
                }
                if (! $account->participant_id) {
                    if (! ($state['participantsLoaded'] ?? false)) {
                        $html = $this->fetch('/recordings');
                        foreach (app(AtlasParser::class)->participants($html) as $email => $id) {
                            StudioAccount::where('email', $email)->update(['participant_id' => $id]);
                        }
                        $state['participantsLoaded'] = true;
                    }
                    $account->refresh();
                    if (! $account->participant_id) {
                        $state['warnings'][] = 'ID peserta belum ditemukan untuk '.$account->email.'. Periksa akses akun pada Atlas; data akun ini belum ditarik.';
                        $state['recent_logs'][] = end($state['warnings']);
                        $this->advance($state);

                        return;
                    }
                }
                $page = $state['page'];
                $html = $this->fetch('/recordings?'.http_build_query(['page' => $page, 'sort' => 'recordedAt', 'direction' => 'desc', 'participantId' => $account->participant_id]));
                $props = app(AtlasParser::class)->recordings($html);
                $records = $props['recordings'];
                $pageSize = max(1, (int) ($props['pageSize'] ?? $props['perPage'] ?? 25));
                $totalPages = max(1, (int) ($props['totalPages'] ?? ceil(($props['total'] ?? count($records)) / $pageSize)));
                if (! $records && (int) ($props['total'] ?? 0) > 0 && $page <= $totalPages) {
                    throw new \RuntimeException('Atlas mengembalikan halaman kosong sebelum seluruh data selesai dibaca. Jalankan ulang sinkronisasi.');
                }
                foreach ($records as $record) {
                    try {
                        $sourceEmail = strtolower(trim((string) ($record['participantEmail'] ?? $account->email)));
                        if ($sourceEmail !== strtolower(trim($account->email))) {
                            throw new \InvalidArgumentException('Email pada rekaman Atlas tidak cocok dengan akun yang diminta; rekaman tidak disimpan.');
                        }
                        $this->ingest($record, $account->email);
                        $state['recordsCount']++;
                    } catch (\InvalidArgumentException $e) {
                        $state['skippedCount'] = ($state['skippedCount'] ?? 0) + 1;
                        $state['warnings'][] = $account->email.' halaman '.$page.': '.$e->getMessage();
                    }
                }
                $state['currentAccount'] = $account->email;
                $state['stage'] = 'scraping';
                $state['message'] = 'Memproses akun '.($index + 1).'/'.$state['totalAccounts'].', halaman '.$page.'.';
                $state['recent_logs'][] = now()->format('H:i:s').' '.$state['message'];
                $state['recent_logs'] = array_slice($state['recent_logs'], -40);
                // Scan all pages: old pending/review changes may exist beyond an old, settled page.
                if ($records && ($page < $totalPages || (! isset($props['total']) && ! isset($props['totalPages']) && count($records) >= $pageSize))) {
                    if ($page >= config('payroll.atlas_max_pages')) {
                        throw new \RuntimeException('Batas halaman sinkronisasi tercapai. Periksa akun atau naikkan batas konfigurasi.');
                    }
                    $state['page']++;
                    $state['percent'] = min(99, (int) floor(($index + $page / $totalPages) / $state['totalAccounts'] * 100));
                    $this->save($state);
                } else {
                    $this->advance($state);
                }
            } catch (\Throwable $e) {
                report($e);
                $state['isRunning'] = false;
                $state['stage'] = 'failed';
                $state['message'] = 'Atlas gagal pada '.($account->email ?? 'persiapan akun').', halaman '.($state['page'] ?? 1).': '.$e->getMessage();
                $state['error'] = $state['message'];
                $state['recent_logs'][] = $state['message'];
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
        $response = Http::timeout(config('payroll.atlas_timeout'))->connectTimeout(5)
            ->retry(3, 250, fn ($e) => $e instanceof \Illuminate\Http\Client\ConnectionException
                || ($e instanceof \Illuminate\Http\Client\RequestException && ($e->response->serverError() || $e->response->status() === 429)), throw: false)
            ->withHeaders(['Cookie' => $cookie, 'Accept' => 'text/html'])
            ->withOptions(['allow_redirects' => false])->get(rtrim(config('payroll.atlas_url'), '/').$path);
        if (! $response->successful()) {
            throw new \RuntimeException('Atlas mengembalikan HTTP '.$response->status().(in_array($response->status(), [301, 302, 303, 307, 308, 401, 403])
                ? '; sesi tidak berlaku atau akses ditolak. Perbarui sesi Atlas pada server.' : '; periksa koneksi atau coba ulang setelah layanan Atlas pulih.'));
        }

        return $response->body();
    }

    public function ingest(array $r, string $email): void
    {
        $id = strtolower(trim((string) ($r['userTaskId'] ?? '')));
        if (! $id || strlen($id) > 64 || empty($r['recordedAt'])) {
            throw new \InvalidArgumentException('Rekaman Atlas tidak memiliki ID/tanggal yang valid.');
        }
        try {
            $dt = CarbonImmutable::parse($r['recordedAt'])->utc();
            $reviewed = ! empty($r['reviewedAt']) ? CarbonImmutable::parse($r['reviewedAt'])->setTimezone('Asia/Makassar')->format('M j, Y, g:i A').' WITA' : null;
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Tanggal rekaman '.$id.' tidak valid.');
        }
        if (! is_numeric($r['durationSeconds'] ?? null) || (float) $r['durationSeconds'] < 0) {
            throw new \InvalidArgumentException('Durasi rekaman '.$id.' tidak valid.');
        }
        $duration = (float) ($r['durationSeconds'] ?? 0);
        $billable = (float) ($r['tileBillableHours'] ?? 0);
        $rejected = (float) ($r['tileRejectedHours'] ?? 0);
        $values = ['participant_email' => strtolower($email), 'task_title' => $r['taskTitle'] ?? '', 'recorded_at' => $dt->format('Y-m-d H:i:s'),
            'recorded_utc' => $dt->setTimezone('Asia/Makassar')->format('M j, Y, g:i A').' WITA',
            'duration_sec' => $duration, 'duration_min' => round($duration / 60, 2), 'processing_status' => $r['processingStatus'] ?? '',
            'billable_status' => ($r['billable'] ?? null) === true ? 'yes' : ((($r['billable'] ?? null) === false && $rejected > 0) ? 'no' : null),
            'billable_min' => round($billable * 60, 2), 'rejected_min' => round($rejected * 60, 2),
            'pending_min' => round((float) ($r['tilePendingHours'] ?? 0) * 60, 2), 'deferred_min' => round((float) ($r['tileDeferredHours'] ?? 0) * 60, 2),
            'reviewed_utc' => $reviewed,
            'reconciliation' => $r['reconciliationStatus'] ?? '', 'atlas_synced_at' => now(), 'last_synced_at' => now()];
        // Only source-controlled fields change. Manual assignments, notes and payment statuses survive every refresh.
        StudioRecording::ingestWithQcTracking($id, $values);
    }

    private function advance(array $state): void
    {
        $state['currentIndex']++;
        $state['page'] = 1;
        $state['percent'] = (int) floor($state['currentIndex'] / $state['totalAccounts'] * 100);
        if ($state['currentIndex'] >= $state['totalAccounts']) {
            $state['isRunning'] = false;
            $state['stage'] = empty($state['warnings']) ? 'completed' : 'failed';
            $state['message'] = 'Atlas: '.$state['recordsCount'].' rekaman disimpan.';
            if (! empty($state['warnings'])) {
                $state['error'] = count($state['warnings']).' masalah ditemukan. '.implode(' | ', array_slice($state['warnings'], -10));
                $state['message'] .= ' Belum lengkap: '.$state['error'];
                $state['recent_logs'] = array_slice(array_merge($state['recent_logs'], $state['warnings']), -40);
            }
            $state['completedAt'] = now()->toIso8601String();
        }
        $this->save($state);
    }

    public function fail(string $message): void
    {
        $this->save(array_merge($this->state(), ['isRunning' => false, 'stage' => 'failed', 'message' => $message, 'error' => $message]));
    }

    private function save(array $state): void
    {
        $state['updatedAt'] = now()->toIso8601String();
        ShiftSetting::updateOrCreate(['setting_key' => 'atlas_sync_state'], ['setting_value' => $state]);
    }
}
