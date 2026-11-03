<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poster Barcode Absensi - {{ $config['studio_name'] ?? 'AIKu Studio' }}</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800;900&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .poster-card { box-shadow: none !important; border: none !important; width: 100% !important; max-width: 100% !important; margin: 0 !important; }
            @page { size: A4 portrait; margin: 1cm; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900 font-sans p-4 sm:p-8 flex flex-col items-center justify-center antialiased" x-data="posterData()" x-init="initPoster()">

    <!-- TOP TOOLBAR (HIDDEN IN PRINT) -->
    <div class="no-print w-full max-w-2xl bg-white border border-slate-200/90 rounded-2xl p-4 mb-6 shadow-sm flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-[#009B7E] flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-900">Poster Barcode Absensi WiFi</h1>
                <p class="text-xs text-slate-500">Siap dicetak di kertas A4 untuk ditempel di dinding studio.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="window.print()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs sm:text-sm font-bold transition flex items-center gap-2 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak Poster (Print A4)</span>
            </button>
            <button type="button" @click="downloadPng()" class="px-3.5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 rounded-xl text-xs sm:text-sm font-semibold transition flex items-center gap-1.5 shadow-2xs">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Unduh PNG</span>
            </button>
            <button type="button" @click="window.close()" class="px-3 py-2 text-slate-400 hover:text-slate-600 rounded-xl text-xs transition">
                Tutup
            </button>
        </div>
    </div>

    <!-- POSTER CANVAS CARD -->
    <div class="poster-card w-full max-w-2xl bg-white rounded-3xl border border-slate-200 shadow-xl p-8 sm:p-12 text-center flex flex-col items-center justify-between relative overflow-hidden">
        
        <!-- Header Branding -->
        <div class="w-full border-b-2 border-slate-100 pb-6 mb-6">
            <div class="flex items-center justify-center gap-3 mb-2">
                <span class="relative flex h-10 w-10 items-center justify-center">
                    <span class="absolute -left-0.5 h-8 w-8 rounded-full border-[3px] border-brand-500"></span>
                    <span class="absolute -right-0.5 h-8 w-8 rounded-full border-[3px] border-emerald-400"></span>
                </span>
                <span class="text-2xl font-black tracking-tight text-slate-900">AIKu</span>
                <span class="rounded-md border border-brand-100 bg-brand-50 px-2 py-0.5 text-xs font-extrabold text-brand-700 uppercase tracking-wider">Studio</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight mt-3 uppercase">Barcode Absensi Masuk</h2>
            <p class="text-xs sm:text-sm font-semibold text-emerald-700 mt-1 flex items-center justify-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Khusus Jaringan WiFi Studio — Waktu WITA</span>
            </p>
        </div>

        <!-- Big Center QR Code with Styling Frame -->
        <div class="my-4 flex flex-col items-center">
            <div class="p-5 bg-white rounded-3xl border-4 border-slate-900 shadow-lg relative">
                <!-- Corner Targets -->
                <div class="absolute -top-1.5 -left-1.5 w-5 h-5 border-t-4 border-l-4 border-emerald-500 rounded-tl-lg"></div>
                <div class="absolute -top-1.5 -right-1.5 w-5 h-5 border-t-4 border-r-4 border-emerald-500 rounded-tr-lg"></div>
                <div class="absolute -bottom-1.5 -left-1.5 w-5 h-5 border-b-4 border-l-4 border-emerald-500 rounded-bl-lg"></div>
                <div class="absolute -bottom-1.5 -right-1.5 w-5 h-5 border-b-4 border-r-4 border-emerald-500 rounded-br-lg"></div>

                <div id="qr-container" class="w-64 h-64 sm:w-72 sm:h-72 flex items-center justify-center">
                    <!-- Canvas / SVG rendered by QR code -->
                    <canvas id="qr-canvas" class="w-full h-full"></canvas>
                </div>
            </div>

            <p class="mt-4 font-mono text-[11px] font-bold text-slate-400 tracking-wider">
                ID KODE: {{ substr($config['qr_secret'] ?? '', 0, 16) }}••••
            </p>
        </div>

        <!-- 3 Simple Steps Instructions -->
        <div class="w-full bg-slate-50 border border-slate-200/80 rounded-2xl p-5 my-6 text-left">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 mb-3 text-center sm:text-left">
                Petunjuk Absensi Masuk Pekerja:
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="flex items-start gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-[#009B7E] font-black text-xs flex items-center justify-center shrink-0">1</span>
                    <p class="text-xs text-slate-700 leading-snug">
                        Sambungkan HP ke <strong>WiFi Studio</strong>
                    </p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-[#009B7E] font-black text-xs flex items-center justify-center shrink-0">2</span>
                    <p class="text-xs text-slate-700 leading-snug">
                        Buka Kamera HP atau <strong>Portal AIKu</strong>
                    </p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-[#009B7E] font-black text-xs flex items-center justify-center shrink-0">3</span>
                    <p class="text-xs text-slate-700 leading-snug">
                        Arahkan kamera ke <strong>QR Code</strong> di atas
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer Notice -->
        <div class="w-full border-t border-slate-100 pt-4 text-center">
            <p class="text-[11px] text-slate-400">
                Sistem secara otomatis mendeteksi koneksi router WiFi Kantor dan mencatat waktu presensi secara akurat.
            </p>
            <p class="text-[10px] text-slate-400 mt-0.5">
                © {{ date('Y') }} {{ $config['studio_name'] ?? 'AIKu Studio' }} • All rights reserved.
            </p>
        </div>

    </div>

    <script>
        function posterData() {
            return {
                qrUrl: @json($qrUrl),
                initPoster() {
                    this.$nextTick(() => {
                        const canvas = document.getElementById('qr-canvas');
                        if (window.QRCode && canvas) {
                            window.QRCode.toCanvas(canvas, this.qrUrl, {
                                width: 320,
                                margin: 1,
                                color: {
                                    dark: '#0f172a',
                                    light: '#ffffff'
                                }
                            }, function (err) {
                                if (err) console.error(err);
                            });
                        }
                    });
                },
                downloadPng() {
                    const canvas = document.getElementById('qr-canvas');
                    if (!canvas) return;
                    const link = document.createElement('a');
                    link.download = 'Barcode_Absensi_AIKu_Studio.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                }
            };
        }
    </script>
</body>
</html>
