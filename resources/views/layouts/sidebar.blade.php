@php
    $menu = [
        ['label' => 'Dashboard', 'path' => 'index.html', 'icon' => 'M3 10l9-7 9 7M5 9v11h5v-6h4v6h5V9'],
        ['label' => 'Task List (Rekaman)', 'path' => 'recordings.html', 'icon' => 'M9 5H5v16h14V5h-4M9 3h6v4H9V3m0 10l2 2 4-4'],
        ['label' => 'Riwayat Slip Gaji', 'path' => 'payslips.html', 'icon' => 'M14 3H5v18h14V8l-5-5v5h5M9 12h6m-6 4h6'],
        ['label' => 'Pengaturan Shift', 'path' => 'shifts.html', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Buat Slip Gaji', 'path' => 'create.html', 'icon' => 'M12 8v8m-4-4h8m5 0a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['label' => 'Teams & Kontributor', 'path' => 'contributors.html', 'icon' => 'M16 21v-2a4 4 0 00-8 0v2M12 11a4 4 0 100-8 4 4 0 000 8m8 10v-2a4 4 0 00-3-3.87M4 21v-2a4 4 0 013-3.87'],
        ['label' => 'Absensi', 'path' => 'attendance.html', 'icon' => 'M9 5H5v16h14V5h-4M9 3h6v4H9V3m0 10l2 2 4-4'],
        ['label' => 'Laporan Harian', 'path' => 'work-reports.html', 'icon' => 'M14 3H5v18h14V8l-5-5v5h5M9 12h6m-6 4h6'],
        ['label' => 'Reporting & Profit', 'path' => 'profit.html', 'icon' => 'M12 3v9h9A9 9 0 1012 3zM16 3.5V8h4.5'],
    ];
    $initials = collect(explode(' ', auth()->user()->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
@endphp
<button type="button" x-show="sidebarOpen" @click="sidebarOpen=false" aria-label="Tutup menu" class="fixed inset-0 z-40 bg-slate-900/30 md:hidden"></button>
<aside x-show="sidebarOpen" @keydown.escape.window="sidebarOpen=false" aria-label="Menu panel" class="fixed inset-y-0 left-0 z-50 flex h-screen w-64 shrink-0 flex-col border-r border-slate-200 bg-white md:sticky md:top-0">
    <div class="flex h-20 shrink-0 items-center justify-between border-b border-slate-100 px-6">
        <a href="/index.html" class="flex items-center gap-3" aria-label="AIKu Studio — Dashboard"><span class="relative flex h-9 w-9 items-center justify-center" aria-hidden="true"><span class="absolute -left-0.5 h-7 w-7 rounded-full border-[3px] border-brand-500"></span><span class="absolute -right-0.5 h-7 w-7 rounded-full border-[3px] border-emerald-400"></span></span><span class="text-xl font-extrabold tracking-tight text-slate-900">AIKu</span><span class="rounded-md border border-brand-100 bg-brand-50 px-1.5 py-0.5 text-xs font-bold text-brand-700">Studio</span></a>
        <button type="button" @click="sidebarOpen=false" aria-label="Tutup menu samping" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 md:hidden"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="2" stroke-linecap="round" d="M6 6l12 12M6 18L18 6"/></svg></button>
    </div>
    <nav aria-label="Menu utama" class="min-h-0 flex-1 space-y-1 overflow-y-auto px-4 py-5">
        <p class="mb-3 px-3.5 text-[11px] font-bold uppercase tracking-wider text-slate-400">Menu Utama</p>
        @foreach($menu as $item)
            @php($active = request()->is($item['path']) || ($item['path'] === 'index.html' && request()->is('/')))
            <a href="/{{ $item['path'] }}" @if($active) aria-current="page" @endif class="flex min-h-[44px] items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm transition {{ $active ? 'bg-brand-50 font-semibold text-brand-700' : 'font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900' }}"><svg class="h-4 w-4 shrink-0 {{ $active ? 'text-brand-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg><span class="min-w-0 flex-1">{{ $item['label'] }}</span>@if($active)<span class="h-1.5 w-1.5 shrink-0 rounded-full bg-brand-500" aria-hidden="true"></span>@endif</a>
        @endforeach
    </nav>
    <footer class="shrink-0 space-y-4 border-t border-slate-100 p-4">
        <div class="flex min-w-0 items-center gap-3"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700" aria-hidden="true">{{ $initials }}</span><div class="min-w-0"><p class="truncate text-xs font-bold text-slate-800" title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</p><p class="mt-1 truncate text-[11px] text-slate-500" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</p></div></div>
        <form method="post" action="/logout">@csrf<button type="submit" class="flex min-h-[44px] w-full items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M9 5H5v14h4m5-14l5 7-5 7m-5-7h10"/></svg><span>Keluar akun</span></button></form>
    </footer>
</aside>
