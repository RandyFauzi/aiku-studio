<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\ShiftSetting;
use App\Models\User;
use App\Services\ShiftSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public const SETTING_KEY = 'studio_attendance_qr_wifi';

    private function contributor(Request $request): Contributor
    {
        abort_unless($request->user()->role === 'contributor', 403);

        if ($request->user()->contributor_id) {
            $person = Contributor::find($request->user()->contributor_id);
            if ($person) {
                return $person;
            }
        }

        $person = Contributor::where('email', $request->user()->email)
            ->orWhere('nama', $request->user()->name)
            ->first();

        if ($person) {
            $request->user()->forceFill(['contributor_id' => $person->id])->saveQuietly();

            return $person;
        }

        $person = Contributor::create([
            'nama' => $request->user()->name ?: 'Contributor',
            'email' => $request->user()->email,
            'role' => 'contributor',
        ]);
        $request->user()->forceFill(['contributor_id' => $person->id])->saveQuietly();

        return $person;
    }

    public function mine(Request $request, ShiftSchedule $schedule)
    {
        $person = $this->contributor($request);
        $data = $request->validate(['month' => 'required|date_format:Y-m']);
        $start = CarbonImmutable::createFromFormat('!Y-m', $data['month'], 'Asia/Makassar');

        return response()->json([
            'name' => $person->nama,
            'today' => CarbonImmutable::now('Asia/Makassar')->toDateString(),
            'today_attendance' => Attendance::where('user_id', $request->user()->id)->where('work_date', CarbonImmutable::now('Asia/Makassar')->toDateString())->first(),
            'schedule' => $schedule->forContributor($person, $data['month']),
            'attendances' => Attendance::where('user_id', $request->user()->id)->whereBetween('work_date', [$start->toDateString(), $start->endOfMonth()->toDateString()])->orderByDesc('work_date')->get(),
        ]);
    }

    public function checkIn(Request $request)
    {
        $person = $this->contributor($request);
        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();
        if ($existing = Attendance::where('user_id', $request->user()->id)->where('work_date', $today)->first()) {
            return response()->json(['success' => true, 'already_checked_in' => true, 'attendance' => $existing]);
        }

        $data = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy_m' => 'required|numeric|min:0|max:1000',
            'captured_at' => 'required|date',
        ]);

        $captured = CarbonImmutable::parse($data['captured_at']);
        if ($captured->diffInMinutes(now()) > 30) {
            throw ValidationException::withMessages(['captured_at' => 'Data lokasi kedaluwarsa. Silakan coba lagi.']);
        }

        $row = DB::transaction(function () use ($request, $person, $today, $data) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            return Attendance::firstOrCreate(
                ['user_id' => $request->user()->id, 'work_date' => $today],
                [
                    'contributor_id' => $person->id,
                    'contributor_name' => $person->nama,
                    'checked_in_at' => now(),
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'accuracy_m' => $data['accuracy_m'],
                    'gps_captured_at' => CarbonImmutable::parse($data['captured_at']),
                ]
            );
        });

        return response()->json(['success' => true, 'already_checked_in' => ! $row->wasRecentlyCreated, 'attendance' => $row]);
    }

    public function checkInQr(Request $request)
    {
        try {
            $person = $this->contributor($request);
            $today = CarbonImmutable::now('Asia/Makassar')->toDateString();

            $data = $request->validate([
                'qr_token' => 'required|string',
            ]);

            $config = $this->getWifiQrConfig();

            // 1. Validate QR Token FIRST
            if (trim($data['qr_token']) !== trim($config['qr_secret'])) {
                throw ValidationException::withMessages([
                    'qr_token' => 'Barcode absensi tidak valid atau telah diperbarui oleh Admin. Silakan scan ulang barcode resmi studio.',
                ]);
            }

            // 2. Validate WiFi IP Gateway
            $clientIp = $this->getClientIp($request);
            $wifiCheckEnabled = (bool) ($config['wifi_check_enabled'] ?? true);
            $allowedIps = (array) ($config['wifi_ips'] ?? []);

            if ($wifiCheckEnabled) {
                if (empty($allowedIps)) {
                    throw ValidationException::withMessages([
                        'wifi' => 'Gagal absensi: WiFi Kantor belum didaftarkan oleh Admin. Silakan hubungi Admin untuk mendaftarkan IP WiFi Studio pada menu Absensi.',
                    ]);
                }

                $isLocal = app()->isLocal() && in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
                $isAllowed = $isLocal || $this->isIpMatched($clientIp, $allowedIps);

                if (!$isAllowed) {
                    $registeredStr = implode(', ', $allowedIps);
                    throw ValidationException::withMessages([
                        'wifi' => "Gagal absensi: Anda terdeteksi menggunakan jaringan pribadi/seluler (IP: {$clientIp}), bukan WiFi Kantor/Studio yang terdaftar ({$registeredStr}). Matikan kuota data seluler dan hubungkan HP Anda ke WiFi Studio sebelum scan barcode.",
                    ]);
                }
            }

            // 3. Check if already checked in today (AFTER WiFi verification to prevent bypassing)
            if ($existing = Attendance::where('user_id', $request->user()->id)->where('work_date', $today)->first()) {
                $formattedTime = CarbonImmutable::parse($existing->checked_in_at)->setTimezone('Asia/Makassar')->format('H:i');

                return response()->json([
                    'success' => true,
                    'already_checked_in' => true,
                    'attendance' => $existing,
                    'message' => "Anda sudah tercatat melakukan absensi masuk hari ini pada pukul {$formattedTime} WITA.",
                ]);
            }

            // 4. Save attendance record safely with fallback fields to avoid MySQL NOT NULL errors
            $row = DB::transaction(function () use ($request, $person, $today, $clientIp) {
                User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

                $attributes = [
                    'contributor_id' => $person->id,
                    'contributor_name' => $person->nama,
                    'checked_in_at' => now(),
                    'latitude' => 0.0,
                    'longitude' => 0.0,
                    'accuracy_m' => 0.0,
                    'gps_captured_at' => now(),
                ];

                if (Schema::hasColumn('attendances', 'checkin_method')) {
                    $attributes['checkin_method'] = 'qr_wifi';
                }
                if (Schema::hasColumn('attendances', 'client_ip')) {
                    $attributes['client_ip'] = $clientIp;
                }
                if (Schema::hasColumn('attendances', 'wifi_verified')) {
                    $attributes['wifi_verified'] = true;
                }

                return Attendance::firstOrCreate(
                    ['user_id' => $request->user()->id, 'work_date' => $today],
                    $attributes
                );
            });

            return response()->json([
                'success' => true,
                'already_checked_in' => ! $row->wasRecentlyCreated,
                'attendance' => $row,
                'message' => 'Absensi masuk berhasil melalui Scan Barcode WiFi Studio!',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('CheckInQr error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return response()->json([
                'message' => 'Gagal absensi: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function isIpMatched(string $clientIp, array $allowedIps): bool
    {
        if (empty($allowedIps)) {
            return false;
        }

        $clientIp = trim($clientIp);
        if ($clientIp === '') {
            return false;
        }

        foreach ($allowedIps as $allowed) {
            $allowed = trim((string) $allowed);
            if ($allowed === '') {
                continue;
            }

            // 1. Exact match (IPv4 or IPv6)
            if ($clientIp === $allowed) {
                return true;
            }

            // 2. Wildcard match (e.g. 140.213.216.*)
            if (str_contains($allowed, '*')) {
                $pattern = '/^' . str_replace(['.', '*'], ['\.', '.*'], $allowed) . '$/';
                if (preg_match($pattern, $clientIp)) {
                    return true;
                }
            }

            // 3. Subnet /24 match for IPv4 (e.g. 140.213.216.5 vs 140.213.216.99 on the same router)
            if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($allowed, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                $clientParts = explode('.', $clientIp);
                $allowedParts = explode('.', $allowed);
                if (count($clientParts) === 4 && count($allowedParts) === 4) {
                    if ($clientParts[0] === $allowedParts[0] && $clientParts[1] === $allowedParts[1] && $clientParts[2] === $allowedParts[2]) {
                        return true;
                    }
                }
            }

            // 4. IPv6 /64 prefix match
            if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($allowed, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $clientBin = @inet_pton($clientIp);
                $allowedBin = @inet_pton($allowed);
                if ($clientBin !== false && $allowedBin !== false) {
                    if (substr($clientBin, 0, 8) === substr($allowedBin, 0, 8)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function index(Request $request)
    {
        $data = $request->validate(['date' => 'required|date_format:Y-m-d', 'contributor_id' => 'nullable|integer']);
        $rows = Attendance::where('work_date', $data['date'])->when($request->filled('contributor_id'), fn ($q) => $q->where('contributor_id', $data['contributor_id']))->orderBy('checked_in_at')->get();

        return response()->json(['date' => $data['date'], 'attendances' => $rows]);
    }

    public function getWifiConfig(Request $request)
    {
        $config = $this->getWifiQrConfig();
        $currentIp = $this->getClientIp($request);

        // Auto-enroll current IP if no IPs have been registered yet by admin
        if (empty($config['wifi_ips']) && $currentIp && !in_array($currentIp, ['127.0.0.1', '::1', 'localhost'], true)) {
            $config['wifi_ips'] = [$currentIp];
            ShiftSetting::updateOrCreate(
                ['setting_key' => self::SETTING_KEY],
                ['setting_value' => $config]
            );
        }

        $qrUrl = url('/me?qr_token=' . $config['qr_secret']);

        return response()->json([
            'config' => $config,
            'current_ip' => $currentIp,
            'qr_url' => $qrUrl,
        ]);
    }

    public function saveWifiConfig(Request $request)
    {
        $data = $request->validate([
            'wifi_ips' => 'nullable|array',
            'wifi_ips.*' => 'string|max:45',
            'wifi_check_enabled' => 'required|boolean',
            'studio_name' => 'nullable|string|max:100',
            'regenerate_qr' => 'nullable|boolean',
        ]);

        $current = $this->getWifiQrConfig();

        $ips = [];
        if (!empty($data['wifi_ips'])) {
            foreach ($data['wifi_ips'] as $ip) {
                $clean = trim((string) $ip);
                if ($clean !== '') {
                    $ips[] = $clean;
                }
            }
        }

        $qrSecret = $current['qr_secret'] ?? 'aiku_qr_' . Str::random(24);
        if (!empty($data['regenerate_qr'])) {
            $qrSecret = 'aiku_qr_' . Str::random(24);
        }

        $updated = [
            'wifi_ips' => array_values(array_unique($ips)),
            'wifi_check_enabled' => (bool) $data['wifi_check_enabled'],
            'qr_secret' => $qrSecret,
            'studio_name' => $data['studio_name'] ?: 'AIKu Studio',
            'updated_at' => now()->toIso8601String(),
        ];

        ShiftSetting::updateOrCreate(
            ['setting_key' => self::SETTING_KEY],
            ['setting_value' => $updated]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan WiFi & Barcode Absensi berhasil disimpan.',
            'config' => $updated,
            'current_ip' => $this->getClientIp($request),
            'qr_url' => url('/me?qr_token=' . $qrSecret),
        ]);
    }

    public function barcodePoster(Request $request)
    {
        $config = $this->getWifiQrConfig();
        $qrUrl = url('/me?qr_token=' . $config['qr_secret']);

        return view('attendance.poster', [
            'config' => $config,
            'qrUrl' => $qrUrl,
        ]);
    }

    public function getWifiQrConfig(): array
    {
        $val = ShiftSetting::where('setting_key', self::SETTING_KEY)->value('setting_value');

        if (empty($val) || !is_array($val) || empty($val['qr_secret'])) {
            $default = [
                'wifi_ips' => [],
                'wifi_check_enabled' => true,
                'qr_secret' => 'aiku_qr_' . Str::random(24),
                'studio_name' => 'AIKu Studio',
                'updated_at' => now()->toIso8601String(),
            ];

            ShiftSetting::updateOrCreate(
                ['setting_key' => self::SETTING_KEY],
                ['setting_value' => $default]
            );

            return $default;
        }

        return $val;
    }

    public function getClientIp(Request $request): string
    {
        $headers = [
            'CF-Connecting-IP',
            'True-Client-IP',
            'X-Real-IP',
            'X-Forwarded-For',
            'X-Client-IP',
        ];

        foreach ($headers as $header) {
            $val = $request->header($header);
            if ($val) {
                $parts = explode(',', $val);
                $ip = trim($parts[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return (string) $request->ip();
    }
}
