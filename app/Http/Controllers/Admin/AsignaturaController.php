<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAsignaturaRequest;
use App\Repositories\AsignaturaRepository;
use App\Services\AdminCatalogService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class AsignaturaController extends Controller
{
    private AsignaturaRepository $asignaturaRepo;

    public function __construct(AsignaturaRepository $asignaturaRepo)
    {
        $this->asignaturaRepo = $asignaturaRepo;
    }

    public function index()
    {
        return view('admin.asignaturas.index', [
            'asignaturas' => DB::table('asignatura')
                ->leftJoin('programa', 'programa.cod', '=', 'asignatura.programa')
                ->leftJoin('plan', 'plan.id', '=', 'asignatura.plan')
                ->leftJoin('institucion', 'institucion.id', '=', 'programa.inst')
                ->orderBy('asignatura.nombre')
                ->select(
                    'asignatura.*',
                    'programa.nombre as programa_nombre',
                    'plan.num as plan_num',
                    'institucion.nombre as institucion_nombre'
                )
                ->get(),
            'programas' => DB::table('programa')
                ->leftJoin('institucion', 'institucion.id', '=', 'programa.inst')
                ->orderBy('programa.nombre')
                ->select('programa.*', 'institucion.nombre as institucion_nombre')
                ->get(),
            'planes' => DB::table('plan')->orderBy('num')->get(),
        ]);
    }

    public function store(StoreAsignaturaRequest $request, AdminCatalogService $service, AuditService $auditService)
    {
        $data = $request->validated();

        $program = DB::table('programa')->where('cod', $data['pr'])->first();
        if (!$program) {
            return back()->withErrors(['pr' => 'Programa no encontrado.'])->withInput();
        }

        $code = trim((string) ($data['co'] ?? ''));
        if ($code === '') {
            $code = $service->nextCourseCode($data['pr']);
        }

        $asignaturaId = $this->asignaturaRepo->create([
            'cod' => $code,
            'nombre' => $data['no'],
            'programa' => $data['pr'],
            'plan' => $data['pl'] ?? null,
            'nivel' => $data['ni'] ?? null,
            'creditos' => isset($data['cr']) ? (string) $data['cr'] : null,
            'ihsemana' => $data['is'] ?? null,
        ]);

        $auditService->log('asignatura.created', [
            'asignatura_cod' => $code,
            'asignatura_nombre' => $data['no'],
            'programa' => $data['pr'],
        ]);

        return back()->with('status', 'Asignatura creada correctamente.');
    }
}

