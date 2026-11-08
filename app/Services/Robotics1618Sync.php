<?php

namespace App\Services;

use App\Models\DeviceAccount;
use App\Models\ShiftSetting;
use App\Models\StudioAccount;
use App\Models\StudioRecording;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class Robotics1618Sync
{
    public const SETTING_KEY = 'robotics_1618_sync_state';

    public function state(): array
    {
        return ShiftSetting::where('setting_key', self::SETTING_KEY)->value('setting_value') ?? [
            'isRunning' => false,
            'stage' => 'idle',
            'percent' => 0,
            'message' => 'Belum ada sinkronisasi 1618.',
            'totalSourceRows' => 0,
            'matchedStudioRows' => 0,
            'recent_logs' => [],
            'updatedAt' => null,
        ];
    }

    public function sync(bool $force = false): array
    {
        $lock = Cache::lock('robotics-1618-sync-lock', 180);

        if (! $lock->get()) {
            $current = $this->state();
            if ($current['isRunning'] ?? false) {
                return [
                    'status' => 'already_running',
                    'message' => 'Proses sinkronisasi 1618 sedang berjalan.',
                    'state' => $current,
                ];
            }
            throw new RuntimeException('Sedang ada sinkronisasi 1618 lain yang berjalan. Coba beberapa saat lagi.');
        }

        $startedAt = microtime(true);
        $tempCookie = tempnam(sys_get_temp_dir(), '1618_c_');
        $tempCsv = tempnam(sys_get_temp_dir(), '1618_d_');

        $this->saveState([
            'isRunning' => true,
            'stage' => 'starting',
            'matchedStudioRows' => 0,
            'totalSourceRows' => 0,
            'skippedCount' => 0,
            'percent' => 5,
            'message' => 'Memulai koneksi ke portal 1618 Robotics...',
            'recent_logs' => [$this->logMsg('Memulai bot sinkronisasi 1618 Robotics...')],
            'startedAt' => now()->toIso8601String(),
            'error' => null,
        ]);

        try {
            $baseUrl = rtrim(config('payroll.robotics_1618_url', 'https://partners.1618robotics.com'), '/');
            $tenant = config('payroll.robotics_1618_tenant', 'kamerakita-ai');
            $username = config('payroll.robotics_1618_username');
            $password = config('payroll.robotics_1618_password');
            $timeout = (int) config('payroll.robotics_1618_timeout', 60);

            if (empty($username) || empty($password)) {
                throw new RuntimeException('Kredensial ROBOTICS_1618_USERNAME atau ROBOTICS_1618_PASSWORD belum diatur di file .env.');
            }

            // 1. Login to 1618 Robotics
            $this->logProgress('Melakukan login ke portal 1618...', 15, 'authenticating');
            $this->login($baseUrl, $tenant, $username, $password, $tempCookie, $timeout);
            $this->logProgress('Login berhasil. Mengunduh data rekaman CSV...', 30, 'downloading');

            // 2. Download CSV directly to temporary file
            $this->downloadCsv("{$baseUrl}/{$tenant}/recordings.csv", $tempCookie, $tempCsv, $timeout);
            $this->logProgress('Download selesai. Membaca akun studio yang dipantau...', 45, 'reading_accounts');

            // 3. Collect active studio accounts (Device Accounts + Studio Accounts)
            $allowedEmails = $this->getAllowedStudioEmails();
            if (empty($allowedEmails)) {
                throw new RuntimeException('Tidak ada akun studio yang terdaftar pada sistem HP & Akun atau Studio Accounts.');
            }

            $allowedLookup = array_flip($allowedEmails);
            $this->logProgress('Memproses rekaman CSV untuk '.count($allowedEmails).' email studio...', 55, 'processing');

            // 4. Stream & Parse CSV row-by-row
            $stats = $this->processCsvStream($tempCsv, $allowedLookup);

            $elapsed = round(microtime(true) - $startedAt, 2);
            $msg = "Sinkronisasi 1618 selesai! Ditemukan {$stats['matched']} rekaman dari total {$stats['total']} baris data ({$elapsed}s).";

            $warning = $stats['skipped'] > 0 ? $stats['skipped'].' rekaman studio tidak valid dilewati. Periksa ID, tanggal, dan durasi pada CSV 1618 lalu jalankan ulang.' : null;
            $this->saveState([
                'isRunning' => false,
                'stage' => $warning ? 'failed' : 'completed',
                'percent' => 100,
                'message' => $warning ? $msg.' '.$warning : $msg,
                'skippedCount' => $stats['skipped'],
                'totalSourceRows' => $stats['total'],
                'matchedStudioRows' => $stats['matched'],
                'completedAt' => now()->toIso8601String(),
                'recent_logs' => array_merge($this->state()['recent_logs'] ?? [], [$this->logMsg($msg)]),
                'error' => $warning,
            ]);

            return [
                'status' => 'success',
                'message' => $msg,
                'total_source_rows' => $stats['total'],
                'matched_studio_rows' => $stats['matched'],
                'skipped_rows' => $stats['skipped'],
                'monitored_emails_count' => count($allowedEmails),
                'elapsed_seconds' => $elapsed,
            ];

        } catch (\Throwable $e) {
            $err = $e->getMessage();
            Log::error('1618 Robotics sync error: '.$err, ['trace' => $e->getTraceAsString()]);

            $this->saveState([
                'isRunning' => false,
                'stage' => 'failed',
                'percent' => 0,
                'message' => 'Gagal: '.$err,
                'error' => $err,
                'recent_logs' => array_merge($this->state()['recent_logs'] ?? [], [$this->logMsg('ERROR: '.$err)]),
            ]);

            throw $e;
        } finally {
            $lock->release();
            @unlink($tempCookie);
            @unlink($tempCsv);
        }
    }

    public function getAllowedStudioEmails(): array
    {
        $deviceEmails = DeviceAccount::pluck('email')->all();
        $studioEmails = StudioAccount::where('active', true)->pluck('email')->all();

        $combined = array_merge($deviceEmails, $studioEmails);
        $normalized = [];

        foreach ($combined as $email) {
            $clean = strtolower(trim((string) $email));
            if ($clean !== '' && filter_var($clean, FILTER_VALIDATE_EMAIL)) {
                $normalized[$clean] = true;
            }
        }

        return array_keys($normalized);
    }

    private function login(string $baseUrl, string $tenant, string $username, string $password, string $cookieFile, int $timeout): void
    {
        $loginUrl = "{$baseUrl}/login";
        $ch = curl_init($loginUrl);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => $username,
                'password' => $password,
                'next' => "/{$tenant}/",
            ]),
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'AIKuStudioSync/1.0',
            CURLOPT_SSL_VERIFYPEER => ! app()->isLocal(),
        ]);

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Gagal menghubungi server 1618: {$curlErr}");
        }

        if ($info['http_code'] !== 200 || str_ends_with($info['url'], '/login')) {
            throw new RuntimeException("Autentikasi 1618 gagal (HTTP {$info['http_code']}). Periksa username & password.");
        }
    }

    private function downloadCsv(string $csvUrl, string $cookieFile, string $destinationPath, int $timeout): void
    {
        $fp = fopen($destinationPath, 'w+b');
        if (! $fp) {
            throw new RuntimeException('Tidak dapat membuka file penyimpanan sementara untuk unduhan CSV.');
        }

        $ch = curl_init($csvUrl);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_COOKIEFILE => $cookieFile,
            CURLOPT_COOKIEJAR => $cookieFile,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_USERAGENT => 'AIKuStudioSync/1.0',
            CURLOPT_SSL_VERIFYPEER => ! app()->isLocal(),
        ]);

        $success = curl_exec($ch);
        $info = curl_getinfo($ch);
        $curlErr = curl_error($ch);
        curl_close($ch);
        fclose($fp);

        if (! $success || $info['http_code'] !== 200) {
            throw new RuntimeException("Gagal mengunduh recordings.csv (HTTP {$info['http_code']}): {$curlErr}");
        }

        if (filesize($destinationPath) === 0) {
            throw new RuntimeException('File recordings.csv dari 1618 kosong.');
        }
    }

    private function processCsvStream(string $csvPath, array $allowedLookup): array
    {
        $handle = fopen($csvPath, 'r');
        if (! $handle) {
            throw new RuntimeException('Gagal membuka file CSV yang telah diunduh.');
        }

        // Read header row
        $header = fgetcsv($handle);
        if (! $header || ! is_array($header)) {
            fclose($handle);
            throw new RuntimeException('Format CSV tidak memiliki baris header yang valid.');
        }

        // Strip UTF-8 BOM if present on first column
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        // Map column name to index
        $colIndex = [];
        foreach ($header as $idx => $name) {
            $colIndex[strtolower(trim($name))] = $idx;
        }

        // Validate essential columns
        $required = ['recording_id', 'email', 'task', 'created_utc', 'duration_s'];
        foreach ($required as $req) {
            if (! isset($colIndex[$req])) {
                fclose($handle);
                throw new RuntimeException("Kolom penting '{$req}' tidak ditemukan dalam CSV 1618.");
            }
        }

        $totalRows = 0;
        $matchedRows = 0;
        $skippedRows = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $totalRows++;

            // Skip empty or corrupted lines
            if (count($row) < 5) {
                continue;
            }

            $email = strtolower(trim((string) ($row[$colIndex['email']] ?? '')));
            if ($email === '' || ! isset($allowedLookup[$email])) {
                continue; // Skip any email that does NOT belong to AIKu Studio!
            }

            $recordingId = strtolower(trim((string) ($row[$colIndex['recording_id']] ?? '')));
            if ($recordingId === '' || strlen($recordingId) > 64) {
                $skippedRows++;
                continue;
            }

            $task = trim((string) ($row[$colIndex['task']] ?? ''));
            $createdUtc = trim((string) ($row[$colIndex['created_utc']] ?? ''));
            if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})[T ]/', $createdUtc, $dateParts)
                || ! checkdate((int) $dateParts[2], (int) $dateParts[3], (int) $dateParts[1])
                || ! is_numeric($row[$colIndex['duration_s']] ?? null)
                || (float) $row[$colIndex['duration_s']] < 0) {
                $skippedRows++;
                continue;
            }
            $durationSec = (float) ($row[$colIndex['duration_s']] ?? 0);
            $status = trim((string) ($row[$colIndex['status'] ?? null] ?? ''));
            $billableRaw = strtolower(trim((string) ($row[$colIndex['billable'] ?? null] ?? '')));
            $billableH = (float) ($row[$colIndex['billable_h'] ?? null] ?? 0);
            $rejectedH = (float) ($row[$colIndex['rejected_h'] ?? null] ?? 0);
            $pendingH = (float) ($row[$colIndex['pending_h'] ?? null] ?? 0);
            $deferredH = (float) ($row[$colIndex['deferred_h'] ?? null] ?? 0);
            $reviewedUtc = trim((string) ($row[$colIndex['reviewed_utc'] ?? null] ?? ''));
            $reconciliation = trim((string) ($row[$colIndex['reconciliation'] ?? null] ?? ''));

            // Parse datetime
            $recordedAt = null;
            $formattedWita = null;
            if ($createdUtc !== '') {
                try {
                    $dt = CarbonImmutable::parse($createdUtc, 'UTC');
                    $recordedAt = $dt->utc()->format('Y-m-d H:i:s');
                    $formattedWita = $dt->setTimezone('Asia/Makassar')->format('M j, Y, g:i A').' WITA';
                } catch (\Throwable $e) {
                    $skippedRows++;
                    continue; // Never move malformed historical records into today's payroll.
                }
            }
            if ($recordedAt === null) {
                $skippedRows++;
                continue;
            }

            $formattedReviewed = null;
            if ($reviewedUtc !== '') {
                try {
                    $rDt = CarbonImmutable::parse($reviewedUtc, 'UTC');
                    $formattedReviewed = $rDt->setTimezone('Asia/Makassar')->format('M j, Y, g:i A').' WITA';
                } catch (\Throwable $e) {
                }
            }

            $billableStatus = null;
            if ($billableRaw === 'yes' || $billableRaw === 'true' || $billableRaw === '1') {
                $billableStatus = 'yes';
            } elseif ($billableRaw === 'no' || $billableRaw === 'false' || ($rejectedH > 0 && $billableH == 0)) {
                $billableStatus = 'no';
            }

            $recordData = [
                'participant_email' => $email,
                'task_title' => $task,
                'recorded_utc' => $formattedWita,
                'recorded_at' => $recordedAt,
                'duration_sec' => round($durationSec, 2),
                'duration_min' => round($durationSec / 60, 2),
                'billable_min' => round($billableH * 60, 2),
                'rejected_min' => round($rejectedH * 60, 2),
                'pending_min' => round($pendingH * 60, 2),
                'deferred_min' => round($deferredH * 60, 2),
                'processing_status' => $status,
                'billable_status' => $billableStatus,
                'reviewed_utc' => $formattedReviewed,
                'reconciliation' => $reconciliation ?: null,
                'last_synced_at' => now(),
            ];

            // Ingest into StudioRecording preserving manual assignments and notes
            StudioRecording::updateOrCreate(
                ['record_id' => $recordingId],
                $recordData
            );

            $matchedRows++;
        }

        fclose($handle);

        return [
            'total' => $totalRows,
            'matched' => $matchedRows,
            'skipped' => $skippedRows,
        ];
    }

    private function logProgress(string $message, int $percent, string $stage): void
    {
        $current = $this->state();
        $logs = $current['recent_logs'] ?? [];
        $logs[] = $this->logMsg($message);

        $this->saveState([
            'isRunning' => true,
            'stage' => $stage,
            'percent' => $percent,
            'message' => $message,
            'recent_logs' => array_slice($logs, -30),
        ]);
    }

    private function logMsg(string $msg): string
    {
        return '['.now('Asia/Makassar')->format('H:i:s').'] '.$msg;
    }

    private function saveState(array $data): void
    {
        $current = $this->state();
        $merged = array_merge($current, $data, ['updatedAt' => now()->toIso8601String()]);
        ShiftSetting::updateOrCreate(['setting_key' => self::SETTING_KEY], ['setting_value' => $merged]);
    }
}
