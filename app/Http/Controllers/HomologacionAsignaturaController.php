<?php

namespace App\Http\Controllers;

use App\Services\HomologacionAsignaturaService;
use Illuminate\Http\Request;
use Throwable;

class HomologacionAsignaturaController extends Controller
{
    public function create(HomologacionAsignaturaService $service)
    {
        return view('homologaciones.asignatura.create', $service->formOptions());
    }

    public function review(Request $request, HomologacionAsignaturaService $service)
    {
        $data = $this->validateInitial($request);

        try {
            $accion = $data['_accion'] ?? 'continuar';
            unset($data['_accion']);

            if ($accion === 'borrador') {
                $homologacionId = $service->saveInitialDraft($data, (string) $request->session()->get('x'));

                return redirect()
                    ->route('homologaciones.borradores.index')
                    ->with('status', 'Borrador guardado correctamente.' . ($homologacionId ? ' Consecutivo: ' . $homologacionId . '.' : ''));
            }

            $result = $service->reviewData($data, (string) $request->session()->get('x'));
        } catch (Throwable $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return view('homologaciones.asignatura.review', $result);
    }

    public function result(Request $request, HomologacionAsignaturaService $service)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'ape' => ['required', 'string', 'max:100'],
            'ide' => ['required', 'string', 'max:20'],
            'per' => ['required', 'string', 'max:20'],
            'pex' => ['required', 'string', 'max:150'],
            'pca' => ['required', 'string', 'max:10'],
            'pla' => ['required', 'integer', 'min:1'],
            'ins' => ['required', 'integer', 'min:1'],
            'tip' => ['required', 'integer', 'between:0,2'],
            'cambio_pensum' => ['nullable', 'boolean'],
            'semestre' => ['nullable', 'string', 'max:10'],
            'obs' => ['nullable', 'string', 'max:2000'],
            'homologar_ciclo_universitario' => ['nullable', 'boolean'],
            'pca_ciclo_universitario' => ['exclude_unless:homologar_ciclo_universitario,1', 'required', 'string', 'max:10', 'not_in:0'],
            'pla_ciclo_universitario' => ['exclude_unless:homologar_ciclo_universitario,1', 'required', 'integer', 'min:1'],
            'homologacion_ciclo_universitario_id' => ['nullable', 'integer', 'min:1'],
            'homologacion_id' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $result = $service->resultData($data, $request->all(), (string) $request->session()->get('x'));
        } catch (Throwable $exception) {
            return back()->withErrors(['general' => $exception->getMessage()])->withInput();
        }

        return view('homologaciones.asignatura.resultado', $result);
    }

    private function validateInitial(Request $request): array
    {
        return $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'ape' => ['required', 'string', 'max:100'],
            'ide' => ['required', 'string', 'max:20'],
            'per' => ['required', 'string', 'max:20'],
            'pca' => ['required', 'string', 'max:10', 'not_in:0'],
            'pla' => ['required', 'integer', 'min:1'],
            'cambio_pensum' => ['nullable', 'boolean'],
            'ext' => ['exclude_if:cambio_pensum,1', 'required', 'string', 'max:150', 'not_in:0'],
            'ins' => ['exclude_if:cambio_pensum,1', 'required', 'integer', 'min:1'],
            'homologar_ciclo_universitario' => ['nullable', 'boolean'],
            'pca_ciclo_universitario' => ['exclude_unless:homologar_ciclo_universitario,1', 'required', 'string', 'max:10', 'not_in:0'],
            'pla_ciclo_universitario' => ['exclude_unless:homologar_ciclo_universitario,1', 'required', 'integer', 'min:1'],
            '_accion' => ['nullable', 'in:borrador,continuar'],
            'homologacion_id' => ['nullable', 'integer', 'min:1'],
        ]);
    }
}
