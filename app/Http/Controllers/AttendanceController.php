<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Contributor;
use App\Models\ShiftSetting;
use App\Models\User;
use App\Services\ContributorIdentity;
use App\Services\ShiftSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AttendanceController extends Controller
{
    public const SETTING_KEY = 'studio_attendance_qr_wifi';

    private function contributor(Request $request): Contributor
    {
        return app(ContributorIdentity::class)->resolve($request->user());
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
        if (abs($captured->diffInMinutes(now())) > 30) {
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
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'accuracy_m' => 'nullable|numeric|min:0|max:10000',
                'captured_at' => 'nullable|date',
            ]);

            $config = $this->getWifiQrConfig();

            // 1. Validate QR Token FIRST
            if (! hash_equals(trim($config['qr_secret']), trim($data['qr_token']))) {
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

                if (! $isAllowed) {
                    $registeredStr = implode(', ', $allowedIps);
                    throw ValidationException::withMessages([
                        'wifi' => "Gagal absensi: Anda terdeteksi menggunakan jaringan pribadi/seluler (IP: {$clientIp}), bukan WiFi Kantor/Studio yang terdaftar ({$registeredStr}). Matikan kuota data seluler dan hubungkan HP Anda ke WiFi Studio sebelum scan barcode.",
                    ]);
                }
            }

            // 3. Optional Geofence validation if Studio coordinates are set
            $hasGps = isset($data['latitude'], $data['longitude']);
            $geofenceEnabled = isset($config['studio_lat'], $config['studio_lng']);
            if ($geofenceEnabled && (! $hasGps || ! isset($data['accuracy_m'], $data['captured_at']))) {
                throw ValidationException::withMessages(['location' => 'GPS diperlukan untuk memverifikasi lokasi studio.']);
            }
            if (isset($data['captured_at']) && abs(CarbonImmutable::parse($data['captured_at'])->diffInMinutes(now())) > 30) {
                throw ValidationException::withMessages(['captured_at' => 'Data lokasi kedaluwarsa. Silakan coba lagi.']);
            }
            if ($geofenceEnabled) {
                $studioLat = (float) $config['studio_lat'];
                $studioLng = (float) $config['studio_lng'];
                $radiusM = (float) ($config['geofence_radius_m'] ?? 150);
                $accuracy = isset($data['accuracy_m']) ? (float) $data['accuracy_m'] : 0.0;
                $distance = $this->calculateDistanceMeters((float) $data['latitude'], (float) $data['longitude'], $studioLat, $studioLng);
                if ($accuracy > max($radiusM, 100)) {
                    throw ValidationException::withMessages(['location' => 'Akurasi GPS belum cukup. Silakan coba lagi di area terbuka.']);
                }
                $effectiveRadius = $radiusM + min($accuracy / 2, 50);

                if ($distance > $effectiveRadius) {
                    $distInt = round($distance);
                    throw ValidationException::withMessages([
                        'location' => "Gagal absensi: Posisi GPS Anda terdeteksi di luar area Studio AIKu ({$distInt} meter dari titik kantor, batas radius {$radiusM} meter). Harap lakukan scan barcode di dalam studio.",
                    ]);
                }
            }

            // 4. Check if already checked in today (AFTER WiFi and location verification to prevent bypassing)
            if ($existing = Attendance::where('user_id', $request->user()->id)->where('work_date', $today)->first()) {
                $formattedTime = CarbonImmutable::parse($existing->checked_in_at)->setTimezone('Asia/Makassar')->format('H:i');

                return response()->json([
                    'success' => true,
                    'already_checked_in' => true,
                    'attendance' => $existing,
                    'message' => "Anda sudah tercatat melakukan absensi masuk hari ini pada pukul {$formattedTime} WITA.",
                ]);
            }

            // 5. Save attendance record safely with real GPS coordinates and WiFi metadata
            $row = DB::transaction(function () use ($request, $person, $today, $clientIp, $data, $hasGps, $wifiCheckEnabled) {
                User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

                $lat = $hasGps ? (float) $data['latitude'] : null;
                $lng = $hasGps ? (float) $data['longitude'] : null;
                $acc = $hasGps && isset($data['accuracy_m']) ? (float) $data['accuracy_m'] : null;
                $capturedAt = $hasGps && ! empty($data['captured_at']) ? CarbonImmutable::parse($data['captured_at']) : null;

                $attributes = [
                    'contributor_id' => $person->id,
                    'contributor_name' => $person->nama,
                    'checked_in_at' => now(),
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'accuracy_m' => $acc,
                    'gps_captured_at' => $capturedAt,
                ];

                if (Schema::hasColumn('attendances', 'checkin_method')) {
                    $attributes['checkin_method'] = 'qr_wifi';
                }
                if (Schema::hasColumn('attendances', 'client_ip')) {
                    $attributes['client_ip'] = $clientIp;
                }
                if (Schema::hasColumn('attendances', 'wifi_verified')) {
                    $attributes['wifi_verified'] = $wifiCheckEnabled;
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
        } catch (HttpExceptionInterface $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('CheckInQr error: '.$e->getMessage()."\n".$e->getTraceAsString());

            return response()->json([
                'message' => 'Gagal menyimpan absensi. Silakan coba lagi atau hubungi admin.',
            ], 422);
        }
    }

    public function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
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
                $pattern = '/^'.str_replace('\*', '[0-9]{1,3}', preg_quote($allowed, '/')).'$/D';
                if (preg_match($pattern, $clientIp)) {
                    return true;
                }
            }

            // Wider network ranges must be configured explicitly by an administrator.
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

        $qrUrl = url('/me?qr_token='.$config['qr_secret']);

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
            'wifi_ips.*' => ['string', 'max:45', function ($attribute, $value, $fail) {
                if (! filter_var($value, FILTER_VALIDATE_IP) && ! preg_match('/^(?:[0-9]{1,3}|\*)(?:\.(?:[0-9]{1,3}|\*)){3}$/D', $value)) {
                    $fail('Gunakan alamat IP atau pola IPv4 wildcard yang valid.');
                }
            }],
            'wifi_check_enabled' => 'required|boolean',
            'studio_name' => 'nullable|string|max:100',
            'studio_lat' => 'nullable|numeric|between:-90,90',
            'studio_lng' => 'nullable|numeric|between:-180,180',
            'geofence_radius_m' => 'nullable|integer|min:20|max:10000',
            'regenerate_qr' => 'nullable|boolean',
        ]);

        $current = $this->getWifiQrConfig();

        $ips = [];
        if (! empty($data['wifi_ips'])) {
            foreach ($data['wifi_ips'] as $ip) {
                $clean = trim((string) $ip);
                if ($clean !== '') {
                    $ips[] = $clean;
                }
            }
        }

        $qrSecret = $current['qr_secret'] ?? 'aiku_qr_'.Str::random(24);
        if (! empty($data['regenerate_qr'])) {
            $qrSecret = 'aiku_qr_'.Str::random(24);
        }

        $studioLat = array_key_exists('studio_lat', $data)
            ? ($data['studio_lat'] !== null ? (float) $data['studio_lat'] : null)
            : ($current['studio_lat'] ?? null);

        $studioLng = array_key_exists('studio_lng', $data)
            ? ($data['studio_lng'] !== null ? (float) $data['studio_lng'] : null)
            : ($current['studio_lng'] ?? null);

        $geofenceRadius = array_key_exists('geofence_radius_m', $data)
            ? ($data['geofence_radius_m'] !== null ? (int) $data['geofence_radius_m'] : 150)
            : ($current['geofence_radius_m'] ?? 150);

        $updated = [
            'wifi_ips' => array_values(array_unique($ips)),
            'wifi_check_enabled' => (bool) $data['wifi_check_enabled'],
            'qr_secret' => $qrSecret,
            'studio_name' => ($data['studio_name'] ?? null) ?: 'AIKu Studio',
            'studio_lat' => $studioLat,
            'studio_lng' => $studioLng,
            'geofence_radius_m' => $geofenceRadius,
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
            'qr_url' => url('/me?qr_token='.$qrSecret),
        ]);
    }

    public function barcodePoster(Request $request)
    {
        $config = $this->getWifiQrConfig();
        $qrUrl = url('/me?qr_token='.$config['qr_secret']);

        return view('attendance.poster', [
            'config' => $config,
            'qrUrl' => $qrUrl,
        ]);
    }

    public function getWifiQrConfig(): array
    {
        $val = ShiftSetting::where('setting_key', self::SETTING_KEY)->value('setting_value');

        if (empty($val) || ! is_array($val) || empty($val['qr_secret'])) {
            $default = [
                'wifi_ips' => [],
                'wifi_check_enabled' => true,
                'qr_secret' => 'aiku_qr_'.Str::random(24),
                'studio_name' => 'AIKu Studio',
                'studio_lat' => null,
                'studio_lng' => null,
                'geofence_radius_m' => 150,
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
        // Only the framework's explicitly trusted proxies may interpret IP headers.
        return (string) $request->ip();
    }
}
