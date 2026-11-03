<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Masuk — AIKu Studio</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head><body class="min-h-screen bg-slate-100 flex items-center justify-center p-4 sm:p-6">
<main class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 sm:p-8"><h1 class="text-2xl font-bold text-slate-900">AIKu Studio</h1><p class="text-slate-600 mt-2 mb-6 text-sm">Masuk ke jadwal, absensi, dan panel sesuai akses akunmu.</p>
@if($errors->any())<p role="alert" class="mb-4 text-sm font-medium rounded-xl bg-red-50 border border-red-200 p-3 text-red-700">{{ $errors->first() }}</p>@endif
<form method="post" action="/login" class="space-y-4">@csrf
<div><label for="email" class="block mb-1.5 text-xs font-semibold text-slate-700">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full h-11 rounded-xl border border-slate-300 px-3.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition"></div>
<div><label for="password" class="block mb-1.5 text-xs font-semibold text-slate-700">Kata sandi</label><input id="password" name="password" type="password" required autocomplete="current-password" class="w-full h-11 rounded-xl border border-slate-300 px-3.5 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 outline-none transition"></div>
<button class="w-full h-11 rounded-xl bg-brand-600 text-white font-semibold hover:bg-brand-700 transition shadow-sm mt-2">Masuk</button>
</form></main></body></html>
