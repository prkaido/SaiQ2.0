<?php

namespace App\Http\Controllers;

use App\Services\HomologacionBorradorService;
use App\Services\HomologacionAsignaturaService;
use App\Services\HomologacionProgramaService;
use Illuminate\Http\Request;
use Throwable;

class HomologacionBorradorController extends Controller
{
    public function index(Request $request, HomologacionBorradorService $service)
    {
        $estado = $request->query('estado', 'borrador');
        if (!in_array($estado, ['borrador', 'completado', 'todos'], true)) {
            $estado = 'borrador';
        }

        return view('homologaciones.borradores.index', [
            'estado' => $estado,
            'homologaciones' => $service->list(
                (string) $request->session()->get('x'),
                (int) $request->session()->get('tus'),
                $estado
            ),
        ]);
    }

    public function show(int $id, Request $request, HomologacionBorradorService $service)
    {
        try {
            $homologacion = $service->findForUser(
                $id,
                (string) $request->session()->get('x'),
                (int) $request->session()->get('tus')
            );
        } catch (Throwable $exception) {
            abort(404);
        }

        return view('homologaciones.borradores.show', [
            'homologacion' => $homologacion,
            'detalles' => $service->detalles($id),
            'auditoria' => $service->auditoria($id),
        ]);
    }

    public function edit(
        int $id,
        Request $request,
        HomologacionBorradorService $borradores,
        HomologacionProgramaService $programas,
        HomologacionAsignaturaService $asignaturas
    ) {
        try {
            $homologacion = $borradores->findForUser(
                $id,
                (string) $request->session()->get('x'),
                (int) $request->session()->get('tus')
            );
        } catch (Throwable $exception) {
            abort(404);
        }

        if (!in_array(($homologacion->estado ?? 'borrador'), ['borrador', 'completado'], true)) {
            return redirect()
                ->route('homologaciones.trazabilidad.show', $homologacion->id)
                ->withErrors(['general' => 'Esta homologacion no esta disponible para edicion.']);
        }

        if (($homologacion->tipo ?? 'programa') === 'asignatura') {
            return view('homologaciones.asignatura.create', $asignaturas->formOptions($homologacion) + [
                'draft' => $homologacion,
            ]);
        }

        return view('homologaciones.programa.create', $programas->formOptions($homologacion) + [
            'draft' => $homologacion,
        ]);
    }
}
