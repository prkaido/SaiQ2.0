<?php

namespace App\Http\Controllers;

use App\Services\ReconocimientoTituloService;
use Illuminate\Http\Request;
use Throwable;

class ReconocimientoTituloController extends Controller
{
    public function create(ReconocimientoTituloService $service)
    {
        return view('reconocimiento.create', $service->formOptions());
    }

    public function generate(Request $request, ReconocimientoTituloService $service)
    {
        $data = $request->validate([
            'tra' => ['required', 'integer', 'in:1,2'],
            'tdo' => ['required', 'integer', 'between:0,4'],
            'per' => ['required', 'string', 'max:20'],
            'nom' => ['required', 'string', 'max:150'],
            'num' => ['required', 'string', 'max:30'],
            'pca' => ['required', 'string', 'max:10', 'not_in:0'],
            'ins' => ['required', 'integer', 'min:1'],
            'ext' => ['required', 'string', 'max:10', 'not_in:0'],
        ]);

        try {
            $result = $service->generate($data, (string) $request->session()->get('x'));
        } catch (Throwable $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return view('reconocimiento.resultado', $result);
    }
}
