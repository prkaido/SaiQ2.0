<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminCatalogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstitucionController extends Controller
{
    public function index()
    {
        $instituciones = DB::table('institucion')
            ->orderBy('nombre')
            ->get();

        return view('admin.instituciones.index', [
            'instituciones' => $instituciones,
        ]);
    }

    public function store(Request $request, AdminCatalogService $service)
    {
        $data = $request->validate([
            'no' => ['required', 'string', 'max:200'],
        ]);

        $abbrev = $service->institutionAbbrev($data['no']);

        DB::table('institucion')->insert([
            'nombre' => $data['no'],
            'abrev' => $abbrev,
        ]);

        $service->audit('CREATE_INSTITUCION', ['nombre' => $data['no'], 'abrev' => $abbrev]);

        return back()->with('status', 'Institucion creada correctamente.');
    }
}
