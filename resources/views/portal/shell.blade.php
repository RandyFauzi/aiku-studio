<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#e4f0dc">
    <title>{{ $pageTitle ?? 'Portal Kontributor' }} — AIKu Studio</title>
        <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="AIKu Studio">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body class="portal" x-data="attendancePortalData()">
    <a href="#portal-content" class="sr-only focus:not-sr-only">Lewati navigasi</a>
    <div class="portal-shell">
        @include('layouts.workspace-sidebar', ['admin' => false])
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
