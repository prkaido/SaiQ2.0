<?php

namespace App\Http\Controllers;

use App\Services\PasswordSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->has('x')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request, PasswordSecurityService $passwords)
    {
        $credentials = $request->validate([
            'us' => ['required', 'string', 'max:50'],
            'pa' => ['required', 'string', 'max:100'],
        ]);

        $usuario = DB::table('usuario')
            ->select('id', 'tipo', 'clave')
            ->where('id', $credentials['us'])
            ->where('activo', '!=', 0)
            ->first();

        if (!$usuario || !$passwords->verify($credentials['pa'], $usuario->clave)) {
            return back()
                ->withErrors(['auth' => 'Acceso incorrecto'])
                ->withInput($request->only('us'));
        }

        if ($passwords->needsRehash($usuario->clave)) {
            DB::table('usuario')
                ->where('id', $usuario->id)
                ->update(['clave' => $passwords->make($credentials['pa'])]);
        }

        $request->session()->regenerate();
        $request->session()->put([
            'x' => $usuario->id,
            'tus' => (int) $usuario->tipo,
            'login_time' => time(),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $this->auditLogin($usuario->id, $request->ip());

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function auditLogin(string $userId, ?string $ip): void
    {
        try {
            DB::table('audit_log')->insert([
                'user_id' => $userId,
                'action' => 'LOGIN',
                'ip_address' => $ip ?: 'UNKNOWN',
                'timestamp' => now(),
            ]);
        } catch (\Throwable $exception) {
            // La auditoria no debe bloquear el inicio de sesion.
        }
    }
}
