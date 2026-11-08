<?php

namespace App\Services;

use App\Models\Contributor;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ShiftConfiguration
{
    public function normalize(array $input, bool $requireIdentity = false, bool $daily = false): array
    {
        $data = Validator::make($input, ['start_time' => 'required|date_format:H:i', 'charging_duration_min' => 'required|integer|min:0|max:1440',
            'session_duration_min' => 'required|integer|min:1|max:1440', 'sessions_count' => 'required|integer|min:1|max:50',
            'tools_count' => 'required|integer|min:1|max:1000', 'hp_per_tool' => 'required|integer|min:1|max:100',
            'hourly_rate' => 'required|numeric|min:0', 'qa_yield_rate' => 'required|numeric|min:0|max:100',
            'day_off' => 'required|in:none,monday,tuesday,wednesday,thursday,friday,saturday,sunday', 'karyawan' => 'present|array|max:1000',
            'karyawan.*.name' => 'required|string|max:255'.(($input['planner_version'] ?? 1) === 2 ? '' : '|distinct:ignore_case'),
            'karyawan.*.short' => 'sometimes|nullable|string|max:255', 'karyawan.*.role' => 'sometimes|nullable|string|max:64',
            'karyawan.*.contributor_id' => 'sometimes|nullable|integer',
            'karyawan.*.alat' => 'required|string|regex:/^Alat [1-9][0-9]*$/',
            'karyawan.*.sessions' => 'present|array|max:50',
            'karyawan.*.sessions.*' => 'integer|min:1|max:'.(int) ($input['sessions_count'] ?? 0),
            'planner_version' => 'sometimes|integer|in:2',
            'session_durations' => (($input['planner_version'] ?? 1) === 2 ? 'required' : 'sometimes').'|array|size:'.(int) ($input['sessions_count'] ?? 0),
            'session_durations.*' => 'required|integer|min:1|max:1440',
            'karyawan.*.status' => 'sometimes|nullable|in:scheduled,izin,sakit,libur',
            'karyawan.*.note' => 'sometimes|nullable|string|max:300'])->validate();
        $durations = $data['session_durations'] ?? array_fill(0, (int) $data['sessions_count'], (int) $data['session_duration_min']);
        if (array_sum($durations) + ($data['sessions_count'] - 1) * $data['charging_duration_min'] > 1440) {
            throw ValidationException::withMessages(['session_durations' => 'Total durasi shift dan jeda maksimal 24 jam.']);
        }
        $modern = ($data['planner_version'] ?? 1) === 2;
        $occupied = [];
        $identities = [];
        $counts = [];
        foreach ($data['karyawan'] as $index => &$worker) {
            if (! empty($worker['contributor_id'])) {
                $person = Contributor::find($worker['contributor_id']);
                if (! $person) {
                    throw ValidationException::withMessages(["karyawan.$index.contributor_id" => 'Kontributor tidak tersedia. Hapus atau pilih ulang anggota ini.']);
                }
                $worker['name'] = $person->nama;
            } else {
                $people = Contributor::where('nama', $worker['name'])->get();
                if ($people->count() > 1) {
                    throw ValidationException::withMessages(["karyawan.$index.name" => 'Nama kontributor tidak unik. Pilih anggota berdasarkan ID.']);
                }
                $person = $people->first();
            }
            $worker['contributor_id'] = $person?->id;
            if ($requireIdentity && ! $person) {
                throw ValidationException::withMessages(["karyawan.$index.contributor_id" => 'Hubungkan anggota ke kontributor yang tersedia.']);
            }
            $identity = $person ? 'id:'.$person->id : 'name:'.mb_strtolower(trim($worker['name']));
            if (isset($identities[$identity])) {
                throw ValidationException::withMessages(["karyawan.$index.name" => 'Kontributor yang sama tidak boleh muncul dua kali dalam jadwal.']);
            }
            $identities[$identity] = true;
            $sessions = array_map('intval', $worker['sessions']);
            if (count(array_unique($sessions)) !== count($sessions)) {
                throw ValidationException::withMessages(["karyawan.$index.sessions" => 'Sesi yang sama tidak boleh dipilih dua kali.']);
            }
            if ((int) substr($worker['alat'], 5) > $data['tools_count']) {
                throw ValidationException::withMessages(["karyawan.$index.alat" => 'Alat yang dipilih tidak tersedia.']);
            }
            foreach ($sessions as $session) {
                if (($worker['status'] ?? 'scheduled') !== 'scheduled') {
                    continue;
                }
                $counts[$session] = ($counts[$session] ?? 0) + 1;
                if ($counts[$session] > min(3, (int) $data['tools_count'])) {
                    throw ValidationException::withMessages(["karyawan.$index.sessions" => 'Shift '.$session.' maksimal 3 orang dan tidak boleh melebihi jumlah alat.']);
                }
                $slot = $worker['alat'].':'.$session;
                if (! $modern && isset($occupied[$slot])) {
                    throw ValidationException::withMessages(["karyawan.$index.sessions" => $worker['alat'].' sesi '.$session.' sudah digunakan '.$occupied[$slot].'.']);
                }
                $occupied[$slot] = $worker['name'];
            }
            sort($sessions);
            $worker['sessions'] = $sessions;
            $worker['daily_work_hrs'] = ($worker['status'] ?? 'scheduled') === 'scheduled'
                ? array_sum(array_map(fn ($session) => $durations[$session - 1], $sessions)) / 60 : 0;
            $worker['daily_prod_hrs'] = $worker['daily_work_hrs'] * $data['hp_per_tool'];
        }
        unset($worker);

        return $data;
    }
}
