@php
    $workspaceAdmin = $admin ?? false;
    $workspaceHome = $workspaceAdmin ? '/' : '/me';
    $workspaceSchedule = $workspaceAdmin ? '/shifts' : '/me/jadwal';
@endphp
@if($workspaceAdmin)
    <button type="button" x-show="sidebarOpen" @click="sidebarOpen=false"
            x-transition.opacity aria-label="Tutup menu" class="workspace-backdrop fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm md:hidden"></button>
@endif
<aside class="portal-sidebar workspace-sidebar" aria-label="{{ $workspaceAdmin ? 'Menu admin' : 'Menu kontributor' }}"
    @if($workspaceAdmin)
        id="workspace-sidebar" x-show="sidebarOpen"
        @keydown.escape.window="sidebarOpen=false"
        @resize.window.debounce.150ms="if (window.innerWidth >= 768 && !sidebarOpen) { sidebarOpen = (localStorage.getItem('aiku_sidebar_open') !== 'false'); } else if (window.innerWidth < 768 && sidebarOpen) { sidebarOpen = false; }"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-150 transform"
        x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
    @endif>
    <div class="workspace-brand-row">
        <a href="{{ $workspaceHome }}" class="portal-brand" aria-label="AIKu Studio, halaman utama">
            <span class="portal-brand-mark" aria-hidden="true">ai.</span> AIKu Studio
        </a>
        @if($workspaceAdmin)
            <button type="button" @click="sidebarOpen=false" aria-label="Tutup menu samping" class="workspace-sidebar-close md:hidden">
                <svg class="portal-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" aria-hidden="true"><path d="m6 6 12 12M6 18 18 6"/></svg>
            </button>
        @endif
    </div>
    <p class="portal-nav-label">{{ $workspaceAdmin ? 'Ruang kerja tim' : 'Ruang kerjamu' }}</p>
    <nav class="workspace-navigation" aria-label="{{ $workspaceAdmin ? 'Navigasi admin' : 'Navigasi portal desktop' }}">
        @if($workspaceAdmin)
            @foreach($sections as $section)
                <div class="workspace-nav-group">
                    <p class="workspace-nav-section">{{ $section['title'] }}</p>
                    @foreach($section['items'] as $item)
                        @php($active = ($item['path'] === '' && (request()->is('/') || request()->is('index.html') || request()->is('index'))) || ($item['path'] !== '' && (request()->is($item['path']) || request()->is($item['path'].'.html') || request()->is($item['path'].'/*'))))
                        <a href="{{ $item['path'] === '' ? '/' : '/'.$item['path'] }}"
                           @click="if (window.innerWidth < 768) sidebarOpen=false"
                           class="portal-nav-link {{ $active ? 'is-active' : '' }}" @if($active) aria-current="page" @endif>
                            <svg class="portal-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}"/></svg>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endforeach
        @else
            @include('portal.navigation')
        @endif
    </nav>
    <div class="portal-sidebar-bottom">
        <div class="portal-sidebar-note">
            @include('portal.icon', ['name' => 'calendar'])
            <p>@if($workspaceAdmin) Tim tertata,<br>kerja lebih terarah. @else Satu sesi lagi,<br>satu langkah maju. @endif</p>
            <a href="{{ $workspaceSchedule }}">{{ $workspaceAdmin ? 'Atur jadwal tim' : 'Lihat jadwalmu' }} @include('portal.icon', ['name' => 'arrow'])</a>
        </div>
        <div class="portal-sidebar-person">
            <span class="portal-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            <div><strong title="{{ auth()->user()->name }}">{{ auth()->user()->name }}</strong><small>{{ $workspaceAdmin ? 'Admin AIKu' : 'Kontributor AIKu' }}</small></div>
        </div>
        <form method="post" action="/logout">@csrf
            <button type="submit" class="portal-logout">@include('portal.icon', ['name' => 'logout']) Keluar akun</button>
        </form>
    </div>
</aside>
