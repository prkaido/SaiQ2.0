<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSaiqSession
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('x')) {
            return redirect()->route('login')->withErrors([
                'auth' => 'Acceso incorrecto',
            ]);
        }

        return $next($request);
    }
}
