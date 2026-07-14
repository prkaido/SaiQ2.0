<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramaRequest;
use App\Repositories\ProgramaRepository;
use App\Services\AdminCatalogService;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class ProgramaController extends Controller
{
    private ProgramaRepository $programaRepo;

    public function __construct(ProgramaRepository $programaRepo)
    {
        $this->programaRepo = $programaRepo;
    }

    public function index()
    {
        return view('admin.programas.index', [
            'programas' => DB::table('programa')
                ->leftJoin('nivel', 'nivel.id', '=', 'programa.nivel')
                ->leftJoin('institucion', 'institucion.id', '=', 'programa.inst')
                ->orderBy('programa.nombre')
                ->select('programa.*', 'nivel.descripcion as nivel_nombre', 'institucion.nombre as institucion_nombre')
                ->get(),
            'instituciones' => DB::table('institucion')->orderBy('nombre')->get(),
            'niveles' => DB::table('nivel')->orderBy('descripcion')->get(),
        ]);
    }

    public function store(StoreProgramaRequest $request, AdminCatalogService $service, AuditService $auditService)
    {
        $data = $request->validated();

        $isPca = (int) $data['pr'] === 1;
        $code = $isPca ? strtoupper((string) $data['co']) : $service->nextProgramCode((int) $data['pr']);

        if ($isPca && strlen($code) !== 3) {
            return back()->withErrors(['co' => 'El programa PCA debe tener codigo de tres cifras.'])->withInput();
        }

        if (!$this->programaRepo->isCodeUniqueForInstitution($code, (int) $data['pr'])) {
            return back()->withErrors(['co' => 'El codigo del programa ya existe.'])->withInput();
        }

        $programaId = $this->programaRepo->create([
            'cod' => $code,
            'nombre' => $data['no'],
            'nivel' => $data['ni'],
            'inst' => (string) $data['pr'],
            'enpca' => $isPca ? 1 : 0,
            'activo' => $data['ac'],
        ]);

        $auditService->log('programa.created', [
            'programa_cod' => $code,
            'programa_nombre' => $data['no'],
            'institucion' => $data['pr'],
        ]);

        return back()->with('status', 'Programa creado correctamente.');
    }
}
