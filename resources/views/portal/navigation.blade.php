@php
    $portalLinks = [
        ['url' => '/me', 'label' => 'Utama', 'icon' => 'home', 'active' => request()->is('me')],
        ['url' => '/me/laporan', 'label' => 'Laporan', 'icon' => 'report', 'active' => request()->is('me/laporan')],
        ['url' => '/me/jadwal', 'label' => 'Jadwal', 'icon' => 'calendar', 'active' => request()->is('me/jadwal')],
        ['url' => '/me/slip', 'label' => 'Slip Gaji', 'icon' => 'wallet', 'active' => request()->is('me/slip')],
        ['url' => '/me/profil', 'label' => 'Profil', 'icon' => 'user', 'active' => request()->is('me/profil*')],
    ];
@endphp
@foreach ($portalLinks as $link)
    <a href="{{ $link['url'] }}" class="portal-nav-link {{ $link['active'] ? 'is-active' : '' }}" @if($link['active']) aria-current="page" @endif>
        @include('portal.icon', ['name' => $link['icon']])
        <span>{{ $link['label'] }}</span>
    </a>
@endforeach
