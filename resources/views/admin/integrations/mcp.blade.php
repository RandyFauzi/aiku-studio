<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AIKu Studio - Integrasi MCP</title>
    <meta name="theme-color" content="#edf6d9">
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/style.css">
    <style>[x-cloak] { display: none !important; }</style>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="/assets/session.js"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="portal admin-theme bg-[#F4F6F8] text-[#1E293B] min-h-screen font-sans flex antialiased"
      x-data="{ sidebarOpen: window.innerWidth >= 768 && localStorage.getItem('aiku_sidebar_open') !== 'false' }"
      x-init="$watch('sidebarOpen', val => { if (window.innerWidth >= 768) localStorage.setItem('aiku_sidebar_open', val); })"
      x-cloak>
    @include('layouts.sidebar')

    <main id="admin-content" class="flex-1 flex flex-col min-w-0 h-screen overflow-y-auto bg-[#F4F6F8]">
        <!-- TOP APP HEADER -->
        <header class="h-16 sm:h-20 bg-white border-b border-slate-200/80 px-4 sm:px-6 flex items-center justify-between shrink-0 sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <button type="button" @click="sidebarOpen = !sidebarOpen" class="p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Buka atau tutup menu" aria-label="Buka atau tutup menu" aria-controls="workspace-sidebar" :aria-expanded="sidebarOpen">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <div class="text-sm font-semibold text-slate-700">
                    Tim & Pengaturan <span class="text-slate-300 mx-1">/</span> Integrasi MCP
                </div>
            </div>
            <div class="hidden sm:block text-xs font-mono bg-slate-100 border border-slate-200 rounded-lg px-3 py-1.5 text-slate-700">
                Endpoint: <span class="font-semibold">{{ $endpointUrl }}</span>
            </div>
        </header>

        <div class="w-full max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Integrasi MCP (Model Context Protocol)</h1>
                <p class="text-sm text-slate-600 mt-1">
                    Hubungkan asisten AI (Antigravity, Claude Code, Codex CLI, Gemini CLI) ke AIKu Studio secara read-only dan teraudit.
                </p>
            </div>
            <div class="sm:hidden text-xs font-mono bg-slate-100 border border-slate-200 rounded-lg px-3 py-2 text-slate-700 break-all">
                Endpoint: <span class="font-semibold">{{ $endpointUrl }}</span>
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('new_mcp_token'))
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 space-y-2">
                <p class="text-sm font-semibold text-amber-900">Kunci Akses Baru (Salin untuk konfigurasi klien AI Anda):</p>
                <div class="flex items-center gap-2">
                    <code id="new-token-value" class="flex-1 font-mono text-xs bg-white border border-amber-200 rounded px-3 py-2 select-all">{{ session('new_mcp_token') }}</code>
                    <button type="button" onclick="navigator.clipboard.writeText(document.getElementById('new-token-value').textContent)" class="px-3 py-2 text-xs font-semibold rounded bg-amber-600 text-white hover:bg-amber-700">
                        Salin
                    </button>
                </div>
            </div>
        @endif

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900 mb-3">Buat Kunci Akses Baru</h2>
                <form method="POST" action="{{ route('admin.integrations.mcp.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="name" class="block text-xs font-medium text-slate-700 mb-1">Nama Koneksi</label>
                        <input id="name" name="name" type="text" required maxlength="100" placeholder="mis. Antigravity Laptop"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-900">
                    </div>
                    <div>
                        <label for="client_type" class="block text-xs font-medium text-slate-700 mb-1">Klien AI</label>
                        <select id="client_type" name="client_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            @foreach ($clients as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="expires_days" class="block text-xs font-medium text-slate-700 mb-1">Masa Berlaku (Hari, opsional)</label>
                        <input id="expires_days" name="expires_days" type="number" min="1" max="365" placeholder="Kosong = tanpa batas waktu"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-slate-900 text-white text-sm font-semibold py-2.5 hover:bg-slate-800">
                        Generate Kunci Akses
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                <h2 class="text-base font-semibold text-slate-900 mb-3">Konfigurasi Klien (Streamable HTTP)</h2>
                <p class="text-xs text-slate-600 mb-3">
                    Tempelkan konfigurasi ini di <code>mcp_config.json</code> (Antigravity), <code>.mcp.json</code> (Claude Code), atau <code>.gemini/settings.json</code> (Gemini CLI):
                </p>
                <pre class="bg-slate-900 text-slate-100 text-xs font-mono rounded-lg p-4 overflow-x-auto">{
  "mcpServers": {
    "aiku-studio": {
      "type": "http",
      "url": "{{ $endpointUrl }}",
      "headers": {
        "Authorization": "Bearer akm_token_akses_anda"
      }
    }
  }
}</pre>
            </div>
        </section>

        <section class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900 mb-3">Daftar Kunci Akses</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs text-slate-500 uppercase">
                            <th class="py-2 pr-4">Nama</th>
                            <th class="py-2 pr-4">Klien</th>
                            <th class="py-2 pr-4">Akhiran</th>
                            <th class="py-2 pr-4">Terakhir Dipakai</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($connections as $conn)
                            <tr>
                                <td class="py-2.5 pr-4 font-medium">{{ $conn->name }}</td>
                                <td class="py-2.5 pr-4 text-xs text-slate-600">{{ $clients[$conn->client_type] ?? $conn->client_type }}</td>
                                <td class="py-2.5 pr-4 font-mono text-xs">...{{ $conn->token_suffix }}</td>
                                <td class="py-2.5 pr-4 text-xs text-slate-600">{{ $conn->last_used_at?->setTimezone('Asia/Makassar')->format('Y-m-d H:i') ?? 'Belum pernah' }}</td>
                                <td class="py-2.5 pr-4 text-xs">
                                    @if ($conn->isRevoked())
                                        <span class="px-2 py-0.5 rounded bg-rose-100 text-rose-700 font-medium">Dicabut</span>
                                    @elseif ($conn->isExpired())
                                        <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-700 font-medium">Kedaluwarsa</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-medium">Aktif</span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-right space-x-2">
                                    @if (! $conn->isRevoked())
                                        <button type="button" onclick="revealMcpToken('{{ route('admin.integrations.mcp.reveal', $conn) }}')" class="text-xs font-medium text-slate-700 hover:text-slate-900 underline">
                                            Lihat / Salin
                                        </button>
                                        <form method="POST" action="{{ route('admin.integrations.mcp.destroy', $conn) }}" class="inline" onsubmit="return confirm('Cabut kunci akses ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-rose-600 hover:text-rose-800 underline">Cabut</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-6 text-center text-xs text-slate-500">Belum ada kunci akses MCP.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <h2 class="text-base font-semibold text-slate-900 mb-3">Log Audit Pemanggilan MCP Terbaru</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 text-slate-500 uppercase">
                            <th class="py-2 pr-3">Waktu (WITA)</th>
                            <th class="py-2 pr-3">Method / Tool</th>
                            <th class="py-2 pr-3">Outcome</th>
                            <th class="py-2 pr-3">HTTP</th>
                            <th class="py-2 pr-3">Latency</th>
                            <th class="py-2">Trace ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentCalls as $call)
                            <tr>
                                <td class="py-2 pr-3 text-slate-600">{{ $call->created_at?->setTimezone('Asia/Makassar')->format('Y-m-d H:i:s') }}</td>
                                <td class="py-2 pr-3 font-mono">{{ $call->tool_name ?: $call->method }}</td>
                                <td class="py-2 pr-3">{{ $call->outcome }}</td>
                                <td class="py-2 pr-3">{{ $call->http_status }}</td>
                                <td class="py-2 pr-3">{{ $call->latency_ms }} ms</td>
                                <td class="py-2 font-mono text-slate-500">{{ $call->trace_id }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-4 text-center text-slate-500">Belum ada aktivitas MCP tercatat.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        </div>
    </main>

    <script>
        async function revealMcpToken(url) {
            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (!res.ok) {
                alert('Gagal membuka kunci akses.');
                return;
            }
            const data = await res.json();
            if (data.token) {
                await navigator.clipboard.writeText(data.token);
                alert('Token disalin ke clipboard:\n' + data.token);
            }
        }
    </script>
</body>
</html>
