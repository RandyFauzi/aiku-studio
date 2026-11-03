<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->user()?->is_active) {
            Auth::logout();
            $request->session()->invalidate();

            return $request->expectsJson() ? response()->json(['message' => 'Akun tidak aktif.'], 401) : redirect('/login');
        }

        return $next($request);
    }
}
