<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Masuk — AIKu Studio</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head><body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">
<main class="w-full max-w-md bg-white rounded-2xl shadow p-8"><h1 class="text-2xl font-bold text-slate-900">AIKu Studio</h1><p class="text-slate-600 mt-2 mb-6">Masuk untuk mengelola operasional dan payroll.</p>
@if($errors->any())<p role="alert" class="mb-4 text-red-700">{{ $errors->first() }}</p>@endif
<form method="post" action="/login" class="space-y-5">@csrf
<div><label for="email" class="block mb-2 font-medium">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full rounded-lg border border-slate-300 p-3"></div>
<div><label for="password" class="block mb-2 font-medium">Kata sandi</label><input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-slate-300 p-3"></div>
<button class="w-full rounded-lg bg-brand-600 text-white p-3 font-semibold hover:bg-brand-700">Masuk</button>
</form></main></body></html>
