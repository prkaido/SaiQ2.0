<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelacionController extends Controller
{
    public function index()
    {
        return view('admin.relaciones.index', [
            'relaciones' => DB::table('progrel')
                ->leftJoin('programa', 'programa.cod', '=', 'progrel.prog')
                ->orderBy('progrel.id')
                ->select('progrel.*', 'programa.nombre as programa_nombre')
                ->get(),
            'directores' => DB::table('usuario')
                ->where('activo', 1)
                ->where('tipo', '!=', 1)
                ->orderBy('id')
                ->get(),
            'programas' => DB::table('programa')
                ->where('inst', '1')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(Request $request, AdminCatalogService $service)
    {
        $data = $request->validate([
            'di' => ['required', 'string', 'max:20', 'not_in:0'],
            'pr' => ['required', 'string', 'max:10', 'not_in:0'],
        ]);

        $exists = DB::table('progrel')
            ->where('dir', $data['di'])
            ->where('prog', $data['pr'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['pr' => 'La relacion ya existe.'])->withInput();
        }

        DB::table('progrel')->insert([
            'dir' => $data['di'],
            'prog' => $data['pr'],
            'activo' => 1,
        ]);

        $service->audit('CREATE_RELACION', $data);

        return back()->with('status', 'Relacion creada correctamente.');
    }
}
