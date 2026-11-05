<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Payslip - {{ $employee_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @include('slips.style')
    </style>
</head>
<body class="p-4 sm:p-8 pt-24 max-w-4xl mx-auto bg-white text-gray-900">
    <!-- Floating Action Bar -->
    <div class="no-print fixed top-0 left-0 w-full bg-gray-900 text-white p-4 shadow-lg flex justify-between items-center z-50">
        <div class="max-w-4xl mx-auto w-full flex flex-wrap justify-between items-center gap-3">
            <div class="font-semibold flex items-center gap-2">
                <span class="w-8 h-8 bg-white text-black rounded-full flex items-center justify-center text-xs font-bold">AI</span>
                <span>Payslip Preview</span>
            </div>
            <div class="flex flex-wrap gap-2 sm:gap-3">
                <a href="{{ auth()->user()?->role === 'admin' ? '/payslips' : '/me/slip' }}" class="px-3.5 py-2 bg-gray-800 border border-gray-700 hover:bg-gray-700 rounded text-xs sm:text-sm font-semibold flex items-center gap-2 transition-colors text-gray-300 hover:text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Kembali ke Riwayat
                </a>
                @if(auth()->user()?->role === 'admin')
                    <a href="{{ $editUrl ?? ('/create?id=' . $id) }}" class="px-3.5 py-2 bg-gray-800 border border-gray-700 hover:bg-gray-700 rounded text-xs sm:text-sm font-semibold flex items-center gap-2 transition-colors text-gray-300 hover:text-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                        Edit Slip
                    </a>
                @endif
                <button type="button" onclick="window.print()" class="px-3.5 py-2 bg-gray-700 hover:bg-gray-600 rounded text-xs sm:text-sm font-semibold transition-colors cursor-pointer">
                    Print
                </button>
                <button id="exportBtn" type="button" onclick="exportPDF()" class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 rounded text-xs sm:text-sm font-semibold flex items-center gap-2 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    Download PDF
                </button>
            </div>
        </div>
    </div>

    @include('slips.content', ['forPdf' => false])

    <script>
        function toggleDayAccordion(bodyId, chevronId) {
            const el = document.getElementById(bodyId);
            const ch = document.getElementById(chevronId);
            if (!el) return;
            el.classList.toggle('hidden');
            if (ch) ch.classList.toggle('rotate-180');
        }

        async function exportPDF() {
            const btn = document.getElementById('exportBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Membuat PDF...';
            btn.disabled = true;
            btn.classList.add('opacity-75', 'cursor-wait');

            try {
                const slipId = @json($id);
                const employeeName = @json($employee_name);
                const isLive = String(slipId).startsWith('LIVE-') || window.location.pathname.startsWith('/slip/live/');
                const endpoint = isLive
                    ? ('/api/payroll/export-live/' + encodeURIComponent(employeeName) + window.location.search)
                    : ('/api/payslips/' + encodeURIComponent(slipId) + '/export');

                const response = await fetch(endpoint, { headers: { 'Accept': 'application/json' } });
                const data = await response.json();
                if (!response.ok || !data.success) {
                    throw new Error(data.message || data.error || 'Gagal membuat PDF');
                }
                const a = document.createElement('a');
                a.href = data.url;
                a.download = data.fileName || ('Payslip_' + String(employeeName).replace(/[^a-zA-Z0-9]/g, '_') + '.pdf');
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
            } catch (error) {
                alert(error.message || 'Gagal terhubung ke server untuk membuat PDF');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.classList.remove('opacity-75', 'cursor-wait');
            }
        }
    </script>
</body>
</html>
