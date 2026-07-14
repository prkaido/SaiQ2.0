<?php

namespace App\Http\Controllers;

use App\Services\HomologacionProgramaService;
use Illuminate\Http\Request;
use Throwable;

class HomologacionProgramaController extends Controller
{
    public function create(HomologacionProgramaService $service)
    {
        return view('homologaciones.programa.create', $service->formOptions());
    }

    public function generate(Request $request, HomologacionProgramaService $service)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'ape' => ['required', 'string', 'max:100'],
            'ide' => ['required', 'string', 'max:20'],
            'per' => ['required', 'string', 'max:20'],
            'pca' => ['required', 'string', 'max:10', 'not_in:0'],
            'pla' => ['required', 'integer', 'min:1'],
            'ext' => ['required', 'string', 'max:10', 'not_in:0'],
            'tipo' => ['nullable', 'in:e,i,r'],
            'semestre' => ['nullable', 'string', 'max:10'],
            'obs' => ['nullable', 'string', 'max:2000'],
            'homologar_ciclo_universitario' => ['nullable', 'boolean'],
            'pca_ciclo_universitario' => ['exclude_unless:homologar_ciclo_universitario,1', 'required', 'string', 'max:10', 'not_in:0'],
            'pla_ciclo_universitario' => ['exclude_unless:homologar_ciclo_universitario,1', 'required', 'integer', 'min:1'],
            '_accion' => ['nullable', 'in:borrador,generar'],
            'homologacion_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $accion = $data['_accion'] ?? 'generar';
            unset($data['_accion']);

            if ($accion === 'borrador') {
                $homologacionId = $service->saveDraft($data, (string) $request->session()->get('x'));

                return redirect()
                    ->route('homologaciones.borradores.index')
                    ->with('status', 'Borrador guardado correctamente.' . ($homologacionId ? ' Consecutivo: ' . $homologacionId . '.' : ''));
            }

            $result = $service->generate($data, (string) $request->session()->get('x'));
        } catch (Throwable $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return view('homologaciones.programa.resultado', $result);
    }
}
