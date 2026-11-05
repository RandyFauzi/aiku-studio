@php
    $paths = [
        'home' => 'm3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10Z',
        'report' => 'M8 4H5v17h14V4h-3M8 3h8v4H8V3Zm0 9h8m-8 4h5',
        'calendar' => 'M8 3v4m8-4v4M4 10h16M5 5h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z',
        'wallet' => 'M20 8V5H4v15h16v-5M4 8h17v7h-6V8m2 3.5h1',
        'user' => 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM5 21v-2a7 7 0 0 1 14 0v2',
        'clock' => 'M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'arrow' => 'M5 12h14m-6-6 6 6-6 6',
        'plus' => 'M12 5v14M5 12h14',
        'scan' => 'M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5M7 7h3v3H7V7Zm7 0h3v3h-3V7Zm-7 7h3v3H7v-3Zm7 0h3v3h-3v-3Z',
        'logout' => 'M9 5H4v14h5m6-14 6 7-6 7m6-7H9',
        'check' => 'm5 12 4 4L19 6',
        'pause' => 'M8 5v14m8-14v14',
        'play' => 'm9 5 11 7-11 7V5Z',
    ];
@endphp
<svg class="portal-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['arrow'] }}"/></svg>
