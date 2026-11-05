<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#e4f0dc">
    <title>{{ $pageTitle ?? 'Portal Kontributor' }} — AIKu Studio</title>
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="portal" x-data="attendancePortalData()">
    <a href="#portal-content" class="sr-only focus:not-sr-only">Lewati navigasi</a>
    <div class="portal-shell">
        <aside class="portal-sidebar">
            <a href="/me" class="portal-brand" aria-label="AIKu Studio, halaman utama">
                <span class="portal-brand-mark">ai.</span> AIKu Studio
            </a>
            <p class="portal-nav-label">Ruang kerjamu</p>
            <nav aria-label="Navigasi portal desktop">@include('portal.navigation')</nav>
            <div class="portal-sidebar-bottom">
                <div class="portal-sidebar-note">
                    @include('portal.icon', ['name' => 'calendar'])
                    <p>Satu sesi lagi,<br>satu langkah maju.</p>
                    <a href="/me/jadwal">Lihat jadwalmu @include('portal.icon', ['name' => 'arrow'])</a>
                </div>
                <div class="portal-sidebar-person">
                    <span class="portal-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div><strong>{{ auth()->user()->name }}</strong><small>Kontributor AIKu</small></div>
                </div>
                <form method="post" action="/logout">@csrf
                    <button type="submit" class="portal-logout">@include('portal.icon', ['name' => 'logout']) Keluar akun</button>
                </form>
            </div>
        </aside>
        <div class="portal-stage">
            <header class="portal-header">
                <a href="/me/profil" class="portal-greeting">
                    <span class="portal-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                    <div><p>Halo, selamat bekerja</p><strong>{{ auth()->user()->name }}</strong></div>
                </a>
                <div class="portal-header-actions">
                    <a href="/me/laporan" class="portal-round-button primary" aria-label="Buat laporan harian">@include('portal.icon', ['name' => 'plus'])</a>
                    <button type="button" class="portal-round-button" @click="openTimerModal()" aria-label="Buka timer rekaman">@include('portal.icon', ['name' => 'clock'])</button>
                </div>
            </header>
            <main id="portal-content" class="portal-content" x-cloak>
                <div x-show="error" class="portal-global-alert" role="alert">
                    <span x-text="error"></span><button type="button" @click="error=''">Tutup</button>
                </div>
                <div x-show="timer.isRunning" class="portal-active-timer">
                    <span>Sesi sedang berjalan · <strong x-text="timerFormatted"></strong></span>
                    <button type="button" class="portal-text-button" @click="openTimerModal()">Buka timer ↗</button>
                </div>
                @yield('content')
            </main>
        </div>
    </div>
    <nav class="portal-mobile-nav" aria-label="Navigasi portal mobile">@include('portal.navigation')</nav>
