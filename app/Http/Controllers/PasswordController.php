<?php

namespace App\Http\Controllers;

use App\Services\PasswordSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PasswordController extends Controller
{
    public function edit()
    {
        return view('auth.change-password');
    }

    public function update(Request $request, PasswordSecurityService $passwords)
    {
        $data = $request->validate([
            'ca' => ['required', 'string', 'max:100'],
            'nc' => ['required', 'string', 'min:6', 'max:100'],
            'rc' => ['required', 'same:nc'],
        ]);

        $userId = (string) $request->session()->get('x');

        $usuario = DB::table('usuario')
            ->select('clave')
            ->where('id', $userId)
            ->first();

        if (!$usuario || !$passwords->verify($data['ca'], $usuario->clave)) {
            return back()->withErrors(['ca' => 'La clave anterior no coincide.']);
        }

        DB::table('usuario')
            ->where('id', $userId)
            ->update(['clave' => $passwords->make($data['nc'])]);

        return back()->with('status', 'Clave actualizada correctamente.');
    }
}
