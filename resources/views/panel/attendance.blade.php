<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Kontributor - AIKu Studio</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    
    <style>
        [x-cloak] { display: none !important; }
        @media print {
            aside, header, .no-print { display: none !important; }
            main { padding: 0 !important; background: white !important; height: auto !important; overflow: visible !important; }
            .print-only { display: block !important; }
            body { background: white !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
<meta name="csrf-token" content="{{ csrf_token() }}">
    <script>window.AIKU_CONFIG = { hourlyRate: @json(config('payroll.hourly_rate')) };</script>
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased" x-data="{sidebarOpen:window.innerWidth >= 768}" @keydown.escape.window="sidebarOpen=false" x-cloak>

    

    <!-- LEFT SIDEBAR -->
    @include('layouts.sidebar')

    <main class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-slate-50">@include('attendance.admin')</main>
</body></html>