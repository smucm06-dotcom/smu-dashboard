<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// The admin knows a password they set for someone, so that person must replace it before anything else.
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->must_change_password) {
            return redirect()->route('settings.index');
        }

        return $next($request);
    }
}
