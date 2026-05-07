<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OrtuAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('ortu_logged_in')) {
            return redirect()->route('ortu.login');
        }

        return $next($request);
    }
}
