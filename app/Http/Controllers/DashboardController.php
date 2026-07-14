<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        return view('dashboard.index', [
            'userId' => (string) $request->session()->get('x'),
            'userType' => (int) $request->session()->get('tus'),
            'usuario' => Usuario::find((string) $request->session()->get('x')),
        ]);
    }
}
