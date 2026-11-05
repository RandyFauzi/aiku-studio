@extends('portal.layout', ['currentPage' => 'home', 'pageTitle' => 'Ruang Kerjamu'])

@section('content')
<div class="portal-heading-row">
    <div>
        <p class="portal-eyebrow">Ruang kerjamu, setiap hari</p>
        <h1 class="portal-title">Buat hari ini<br><span>lebih produktif.</span></h1>
    </div>
    <p class="portal-date" x-text="dateLabel(today)"></p>
</div>

<div class="portal-dashboard-grid">
    <div class="portal-stack">
        @include('portal.streak')
        <section class="portal-dark-card portal-overview" aria-labelledby="presence-heading">
            <div class="portal-card-heading">
                <h2 id="presence-heading">Aktivitas hari ini</h2>
                @include('portal.icon', ['name' => 'calendar'])
            </div>
            <div class="portal-progress-row">
                <div class="portal-progress-ring" role="img" :aria-label="todayAttendance ? 'Kehadiran hari ini sudah tercatat' : 'Kehadiran hari ini belum tercatat'">
                    <svg viewBox="0 0 120 120" aria-hidden="true">
                        <circle cx="60" cy="60" r="49" fill="none" stroke="#ffffff" stroke-width="9" opacity=".16"/>
                        <circle cx="60" cy="60" r="49" fill="none" stroke="#d8efb8" stroke-width="9" stroke-linecap="round" pathLength="100" stroke-dasharray="100" :stroke-dashoffset="todayAttendance ? 0 : 100"/>
                    </svg>
                    <div class="ring-value">
                        <strong x-text="loading ? '…' : (todayAttendance ? '100%' : '0%')"></strong>
                        <small>Kehadiran</small>
                    </div>
                </div>
                <div class="portal-progress-stats">
                    <div class="portal-stat">
                        @include('portal.icon', ['name' => 'calendar'])
                        <div><strong x-text="loading ? '…' : todaySchedule.sessions.length"></strong><small>Sesi terjadwal</small></div>
                    </div>
                    <div class="portal-stat">
                        @include('portal.icon', ['name' => 'check'])
                        <div><strong x-text="loading ? '…' : (todayAttendance ? 'Sudah masuk' : 'Belum masuk')"></strong><small>Absensi hari ini</small></div>
                    </div>
                    <div class="portal-stat">
                        @include('portal.icon', ['name' => 'report'])
                        <div><strong x-text="todayReportSubmitted === null ? 'Cek laporan' : (todayReportSubmitted ? 'Terkirim' : 'Belum dikirim')"></strong><small>Laporan harian</small></div>
                    </div>
                </div>
            </div>
            <button type="button" class="portal-primary-button" @click="openScanner()" :disabled="checking || loading || !!todayAttendance">
                @include('portal.icon', ['name' => 'scan'])
                <span x-text="checking ? 'Memverifikasi…' : (todayAttendance ? 'Kehadiran tercatat' : 'Scan untuk masuk')"></span>
            </button>
            <button type="button" class="portal-gps-button" x-show="!todayAttendance" @click="checkIn()" :disabled="checking || loading">Atau masuk menggunakan GPS</button>
            <template x-if="todayAttendance">
                <p class="portal-presence-time">Masuk pukul <span x-text="time(todayAttendance.checked_in_at)"></span> WITA</p>
            </template>
        </section>

        <section class="portal-tasks" aria-labelledby="sessions-heading">
            <div class="portal-card-heading">
                <h2 id="sessions-heading">Jadwal hari ini</h2>
                <a href="/me/jadwal">Lihat semua ↗</a>
            </div>
            <div class="portal-status-pills">
                <span class="portal-status-pill highlight"><b x-text="todaySchedule.sessions.length"></b> Sesi</span>
                <span class="portal-status-pill"><b x-text="totalMonthAttendances"></b> Hari hadir</span>
                <span class="portal-status-pill"><b x-text="slips.length"></b> Slip</span>
            </div>
            <template x-for="session in todaySchedule.sessions" :key="session.id">
                <a href="/me/jadwal" class="portal-session-card">
                    <div class="portal-session-meta"><span class="portal-tag">Terjadwal</span><span class="portal-round-button" aria-hidden="true">@include('portal.icon', ['name' => 'arrow'])</span></div>
                    <h3 x-text="'Sesi ' + session.id + ' · Rekaman studio'"></h3>
                    <div class="portal-session-meta">
                        <time>@include('portal.icon', ['name' => 'clock'])<span x-text="session.start + ' – ' + session.end"></span></time>
                        <span x-text="session.tool"></span>
                    </div>
                </a>
            </template>
            <div class="portal-empty" x-show="!loading && !todaySchedule.sessions.length">
                Belum ada sesi hari ini.<br>Jadwal berikutnya bisa kamu lihat di kalender.
            </div>
            <div class="portal-empty" x-show="loading" role="status">Memuat jadwalmu…</div>
        </section>
    </div>
    <div class="portal-stack">
        <section class="portal-card portal-timer-card" aria-labelledby="timer-heading">
            <div class="portal-card-heading">
                <h2 id="timer-heading">Waktunya fokus</h2>
                <button type="button" class="portal-round-button" @click="openTimerModal()" aria-label="Pengaturan timer">@include('portal.icon', ['name' => 'clock'])</button>
            </div>
            <div class="portal-timer-face">
                <p x-text="timer.isRunning ? 'Sesi sedang berjalan' : 'Siap mulai sesi rekaman?'"></p>
                <strong x-text="timerFormatted"></strong>
            </div>
            <div class="portal-timer-track" role="progressbar" aria-label="Progres timer" :aria-valuenow="timerProgressPercent" aria-valuemin="0" aria-valuemax="100"><span :style="'width:' + timerProgressPercent + '%'"></span></div>
            <div class="portal-preset-row">
                <template x-for="minutes in [15,20,30]" :key="minutes">
                    <button type="button" class="portal-preset" :class="timer.selectedPreset === minutes ? 'is-selected' : ''" :aria-pressed="timer.selectedPreset === minutes" @click="setTimerPreset(minutes)" x-text="minutes + ' menit'"></button>
                </template>
            </div>
            <div class="portal-timer-actions">
                <button type="button" class="portal-primary-button" @click="toggleTimer()">
                    <span x-show="!timer.isRunning">@include('portal.icon', ['name' => 'play'])</span>
                    <span x-show="timer.isRunning">@include('portal.icon', ['name' => 'pause'])</span>
                    <span x-text="timer.isRunning ? 'Jeda sesi' : 'Mulai sesi'"></span>
                </button>
                <button type="button" class="portal-text-button" @click="resetTimer()">Reset</button>
            </div>
            <button type="button" class="portal-text-button portal-manual-timer" @click="openTimerModal()">Atur durasi sendiri ↗</button>
        </section>
        <a href="/me/laporan" class="portal-report-shortcut">
            @include('portal.icon', ['name' => 'report'])
            <div><h3>Ceritakan hasil harimu.</h3><p>Catat durasi & HP yang dipakai.</p></div>
            @include('portal.icon', ['name' => 'arrow'])
        </a>
    </div>
</div>
@endsection
