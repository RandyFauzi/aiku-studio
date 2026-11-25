<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureContributor
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless(
            $user && in_array($user->role, ['contributor', 'admin'], true),
            403,
            'Halaman ini hanya untuk kontributor atau administrator.'
        );

        return $next($request);
    }
}
