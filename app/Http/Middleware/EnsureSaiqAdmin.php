<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSaiqAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->session()->has('x')) {
            return redirect()->route('login')->withErrors([
                'auth' => 'Acceso incorrecto',
            ]);
        }

        if ($request->session()->get('tus') !== 1) {
            abort(403, 'Acceso denegado.');
        }

        return $next($request);
    }
}
