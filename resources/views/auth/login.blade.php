<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#ecfccb">
    <title>Masuk — AIKu Studio</title>
    <link rel="preload" href="/fonts/manrope-latin-variable.woff2" as="font" type="font/woff2" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @font-face {
            font-family: 'Manrope';
            src: url('/fonts/manrope-latin-variable.woff2') format('woff2');
            font-weight: 200 800;
            font-style: normal;
            font-display: swap;
        }
        body {
            font-family: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="min-h-screen bg-white text-slate-800 relative overflow-x-hidden flex flex-col justify-center selection:bg-emerald-100 selection:text-emerald-900"
      x-data="{ showPassword: false, showForgotModal: false }">

    <!-- Ambient Decorative Organic Background Shapes -->
    <div class="fixed inset-0 pointer-events-none -z-10 overflow-hidden" aria-hidden="true">
        <!-- Top Left Aura Glow -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-lime-100/70 rounded-full blur-3xl opacity-80"></div>
        <!-- Bottom Right Aura Glow -->
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-emerald-100/60 rounded-full blur-3xl opacity-75"></div>
        <!-- Center Soft Light Tint -->
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[700px] bg-radial from-lime-50/50 via-emerald-50/30 to-transparent blur-2xl"></div>

        <!-- Decorative background organic contour lines (SVG) -->
        <svg class="absolute top-0 right-0 w-80 h-80 text-lime-200/40 opacity-70 transform translate-x-12 -translate-y-12" viewBox="0 0 200 200" fill="currentColor">
            <path d="M45,-60C58,-52,69,-39,74,-24C79,-9,78,8,72,23C66,38,55,51,41,60C27,69,10,74,-6,72C-22,70,-38,61,-49,49C-60,37,-66,22,-69,6C-72,-10,-72,-27,-64,-39C-56,-51,-40,-58,-25,-64C-10,-70,6,-75,22,-71C38,-67,45,-60,45,-60Z" transform="translate(100 100)" />
        </svg>
        <svg class="absolute bottom-0 left-0 w-72 h-72 text-emerald-200/35 opacity-70 transform -translate-x-12 translate-y-12" viewBox="0 0 200 200" fill="currentColor">
            <path d="M42,-58C54,-50,64,-38,69,-24C74,-10,74,6,69,21C64,36,54,50,40,58C26,66,8,68,-8,66C-24,64,-40,58,-52,47C-64,36,-72,20,-73,3C-74,-14,-68,-32,-56,-43C-44,-54,-26,-58,-9,-59C8,-60,26,-58,42,-58Z" transform="translate(100 100)" />
        </svg>
    </div>

    <!-- Main Container: Dynamic Mobile and Desktop Layout -->
    <main class="w-full max-w-7xl mx-auto px-4 py-6 sm:px-6 sm:py-10 lg:px-10 lg:py-12 my-auto">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 xl:gap-16 items-center">
            
            <!-- Left Column (Desktop) / Top Section (Mobile): Animated Illustration (Large & Prominent) -->
            <div class="lg:col-span-7 flex flex-col items-center justify-center relative w-full">
                <!-- Soft Green Aura Frame behind the illustration -->
                <div class="absolute inset-0 w-3/4 max-w-lg mx-auto bg-gradient-to-tr from-emerald-200/80 via-lime-200/70 to-emerald-100/50 rounded-full blur-3xl -z-10 scale-110 opacity-90 pointer-events-none"></div>
                
                <div class="relative w-full max-w-[440px] sm:max-w-[520px] lg:max-w-[620px] xl:max-w-[700px] flex items-center justify-center p-1 sm:p-2">
                    <img src="{{ asset('assets/object/Login page.gif') }}" 
                         alt="Ilustrasi Kontributor dan Robot AIKu Studio" 
                         class="w-full h-auto object-contain drop-shadow-md select-none pointer-events-none transition-transform duration-300"
                         loading="eager">
                </div>
            </div>

            <!-- Right Column (Desktop) / Bottom Section (Mobile): Login Form -->
            <div class="lg:col-span-5 w-full max-w-md mx-auto flex flex-col justify-center">
                
                <!-- Header with Playful Lime Radiant Accent Marks -->
                <div class="text-center mb-6 sm:mb-8">
                    <div class="inline-flex items-center justify-center gap-3">
                        <!-- Left Radiant Accent Ticks -->
                        <svg class="w-7 h-7 text-lime-500 shrink-0 select-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">
                            <path d="M7 6L3 4" />
                            <path d="M8 12H2" />
                            <path d="M7 18L3 20" />
                        </svg>

                        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">Login</h1>

                        <!-- Right Radiant Accent Ticks -->
                        <svg class="w-7 h-7 text-lime-500 shrink-0 select-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round">
                            <path d="M17 6L21 4" />
                            <path d="M16 12H22" />
                            <path d="M17 18L21 20" />
                        </svg>
                    </div>

                    <p class="mt-2 text-sm sm:text-base font-medium text-slate-600">
                        Masuk ke akunmu untuk mulai menggunakan aplikasi
                    </p>
                </div>

                <!-- Error Alert Message -->
                @if($errors->any())
                    <div role="alert" class="mb-5 p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs sm:text-sm font-semibold flex items-start gap-2.5 shadow-2xs">
                        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        <span class="flex-1">{{ $errors->first() }}</span>
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="/login" class="space-y-4 sm:space-y-4.5">
                    @csrf

                    <!-- Input 1: Email atau Username (Pill Style) -->
                    <div class="relative">
                        <label for="login-identifier" class="sr-only">Email atau Username</label>
                        <div class="relative flex items-center w-full rounded-full border-2 border-slate-300 bg-white transition-all duration-200 focus-within:border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-500/10 shadow-2xs">
                            <div class="pl-4 sm:pl-5 pr-2.5 text-slate-400 pointer-events-none flex items-center">
                                <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="20" height="16" x="2" y="4" rx="2"/>
                                    <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                                </svg>
                            </div>
                            <input id="login-identifier" 
                                   name="email" 
                                   type="text" 
                                   value="{{ old('email') }}" 
                                   required 
                                   autofocus 
                                   autocomplete="username" 
                                   placeholder="Email atau Username"
                                   class="w-full h-12 sm:h-13 pr-5 bg-transparent text-sm sm:text-base font-semibold text-slate-800 placeholder-slate-400 focus:outline-none">
                        </div>
                    </div>

                    <!-- Input 2: Kata Sandi with Toggle Visibility (Pill Style) -->
                    <div class="relative">
                        <label for="login-password" class="sr-only">Kata Sandi</label>
                        <div class="relative flex items-center w-full rounded-full border-2 border-slate-300 bg-white transition-all duration-200 focus-within:border-emerald-500 focus-within:ring-4 focus-within:ring-emerald-500/10 shadow-2xs">
                            <div class="pl-4 sm:pl-5 pr-2.5 text-slate-400 pointer-events-none flex items-center">
                                <svg class="w-5 h-5 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </div>
                            <input id="login-password" 
                                   name="password" 
                                   :type="showPassword ? 'text' : 'password'" 
                                   required 
                                   autocomplete="current-password" 
                                   placeholder="Kata Sandi"
                                   class="w-full h-12 sm:h-13 pr-3 bg-transparent text-sm sm:text-base font-semibold text-slate-800 placeholder-slate-400 focus:outline-none">
                            <button type="button" 
                                    @click="showPassword = !showPassword" 
                                    aria-label="Tampilkan atau sembunyikan kata sandi" 
                                    class="pr-4 sm:pr-5 pl-2 text-slate-400 hover:text-slate-700 focus:outline-none transition-colors">
                                <!-- Eye Open Icon -->
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                <!-- Eye Closed/Slashed Icon -->
                                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/>
                                    <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/>
                                    <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/>
                                    <line x1="2" x2="22" y1="2" y2="22"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Row: Ingat Saya & Lupa Kata Sandi -->
                    <div class="flex items-center justify-between pt-1 px-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" 
                                   name="remember" 
                                   value="1" 
                                   class="w-4 h-4 rounded border-2 border-slate-300 text-emerald-600 focus:ring-emerald-500 focus:ring-offset-0 transition cursor-pointer">
                            <span class="text-xs sm:text-sm font-semibold text-slate-700">Ingat saya</span>
                        </label>

                        <button type="button" 
                                @click="showForgotModal = true" 
                                class="text-xs sm:text-sm font-bold text-emerald-700 hover:text-emerald-800 hover:underline transition">
                            Lupa kata sandi?
                        </button>
                    </div>

                    <!-- Submit Button: Pill Shape with Subtle Radiant Green Gradient -->
                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full h-12 sm:h-13 rounded-full bg-gradient-to-r from-emerald-500 via-emerald-600 to-emerald-700 hover:from-emerald-600 hover:to-emerald-800 active:scale-[0.99] text-white font-bold text-base sm:text-lg shadow-md shadow-emerald-600/25 transition-all duration-200 flex items-center justify-center gap-2">
                            <!-- Left Radiant Accent Ticks -->
                            <svg class="w-5 h-5 text-emerald-200 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round">
                                <path d="M6 5L3 3" />
                                <path d="M7 10H2" />
                                <path d="M6 15L3 17" />
                            </svg>
                            <span class="tracking-wide">Masuk</span>
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M5 12h14" />
                                <path d="m12 5 7 7-7 7" />
                            </svg>
                        </button>
                    </div>
                </form>

                <!-- Registered Only Notice (No Google Login & No Registration per instructions) -->
                <div class="mt-8 text-center border-t border-slate-100 pt-5">
                    <p class="text-xs text-slate-500 font-medium">
                        Hanya akun kontributor dan tim yang telah terdaftar yang dapat masuk.<br class="hidden sm:inline"> Hubungi admin AIKu Studio untuk aktivasi atau bantuan akses.
                    </p>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal: Bantuan Lupa Kata Sandi -->
    <div x-show="showForgotModal" 
         x-cloak 
         x-transition:enter="ease-out duration-200" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100" 
         x-transition:leave="ease-in duration-150" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
         @keydown.escape.window="showForgotModal = false">
        
        <div class="bg-white rounded-3xl border border-slate-200 w-full max-w-sm overflow-hidden shadow-2xl p-6 text-center transform transition-all"
             @click.outside="showForgotModal = false">
            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            
            <h3 class="text-lg font-bold text-slate-900 mb-2">Bantuan Kata Sandi</h3>
            <p class="text-xs sm:text-sm text-slate-600 mb-6 leading-relaxed">
                Untuk keamanan akun kontributor, reset kata sandi dikelola langsung oleh tim manajemen. Silakan hubungi Administrator atau Lead Studio Anda untuk pembaruan kata sandi.
            </p>
            
            <button type="button" 
                    @click="showForgotModal = false" 
                    class="w-full h-11 rounded-full bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm transition">
                Mengerti
            </button>
        </div>
    </div>

</body>
</html>
