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
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    public const SETTING_KEY = 'studio_attendance_qr_wifi';

    private function contributor(Request $request): Contributor
    {
        abort_unless($request->user()->role === 'contributor', 403);

        return Contributor::findOrFail($request->user()->contributor_id);
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
        $fields = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy_m' => 'required|numeric|min:0|max:1000000',
            'captured_at' => 'required|date',
        ]);
        $captured = CarbonImmutable::parse($fields['captured_at'])->utc();
        if ($captured->lt(CarbonImmutable::now()->subMinutes(5)) || $captured->gt(CarbonImmutable::now()->addMinute())) {
            throw ValidationException::withMessages(['captured_at' => 'Lokasi sudah kedaluwarsa. Ambil GPS kembali dan periksa jam perangkat.']);
        }
        $clientIp = $this->getClientIp($request);
        $row = DB::transaction(function () use ($request, $person, $today, $fields, $captured, $clientIp) {
            // Lock the account so concurrent clicks cannot create two check-ins.
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            return Attendance::firstOrCreate(
                ['user_id' => $request->user()->id, 'work_date' => $today],
                [
                    'contributor_id' => $person->id,
                    'contributor_name' => $person->nama,
                    'checked_in_at' => now(),
                    'checkin_method' => 'gps',
                    'client_ip' => $clientIp,
                    'latitude' => $fields['latitude'],
                    'longitude' => $fields['longitude'],
                    'accuracy_m' => $fields['accuracy_m'],
                    'gps_captured_at' => $captured,
                ]
            );
        });

        return response()->json(['success' => true, 'already_checked_in' => ! $row->wasRecentlyCreated, 'attendance' => $row]);
    }

    public function checkInQr(Request $request)
    {
        $person = $this->contributor($request);
        $today = CarbonImmutable::now('Asia/Makassar')->toDateString();

        if ($existing = Attendance::where('user_id', $request->user()->id)->where('work_date', $today)->first()) {
            return response()->json([
                'success' => true,
                'already_checked_in' => true,
                'attendance' => $existing,
                'message' => 'Anda sudah melakukan absensi masuk hari ini.',
            ]);
        }

        $data = $request->validate([
            'qr_token' => 'required|string',
        ]);

        $config = $this->getWifiQrConfig();

        // 1. Validate QR Token
        if (trim($data['qr_token']) !== trim($config['qr_secret'])) {
            throw ValidationException::withMessages([
                'qr_token' => 'Barcode absensi tidak valid atau telah diperbarui oleh Admin. Silakan scan ulang barcode resmi studio.',
            ]);
        }

        // 2. Validate WiFi IP Gateway
        $clientIp = $this->getClientIp($request);
        $wifiCheckEnabled = (bool) ($config['wifi_check_enabled'] ?? true);
        $allowedIps = (array) ($config['wifi_ips'] ?? []);

        if ($wifiCheckEnabled && !empty($allowedIps)) {
            $isLocal = app()->isLocal() && in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
            $isAllowed = in_array($clientIp, $allowedIps, true) || $isLocal;

            if (!$isAllowed) {
                throw ValidationException::withMessages([
                    'wifi' => "Gagal absensi: Anda terdeteksi menggunakan jaringan dengan IP ({$clientIp}), bukan WiFi Kantor/Studio. Pastikan HP Anda telah terhubung ke WiFi Studio sebelum scan barcode.",
                ]);
            }
        }

        // 3. Save attendance record
        $row = DB::transaction(function () use ($request, $person, $today, $clientIp) {
            User::whereKey($request->user()->id)->lockForUpdate()->firstOrFail();

            return Attendance::firstOrCreate(
                ['user_id' => $request->user()->id, 'work_date' => $today],
                [
                    'contributor_id' => $person->id,
                    'contributor_name' => $person->nama,
                    'checked_in_at' => now(),
                    'checkin_method' => 'qr_wifi',
                    'client_ip' => $clientIp,
                    'wifi_verified' => true,
                ]
            );
        });

        return response()->json([
            'success' => true,
            'already_checked_in' => ! $row->wasRecentlyCreated,
            'attendance' => $row,
            'message' => 'Absensi masuk berhasil melalui Scan Barcode WiFi Studio!',
        ]);
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
            'X-Real-IP',
            'X-Forwarded-For',
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
