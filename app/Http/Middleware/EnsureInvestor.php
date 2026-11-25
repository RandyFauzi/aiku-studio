<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureInvestor
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless(
            $user && $user->role === 'investor' && $user->investor()->exists(),
            403,
            'Halaman ini hanya untuk akun investor.'
        );

        return $next($request);
    }
}
