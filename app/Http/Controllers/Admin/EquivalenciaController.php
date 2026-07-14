<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminCatalogService;
use App\Support\AcademicText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EquivalenciaController extends Controller
{
    public function index()
    {
        $asignaturasPca = $this->asignaturasPca();

        return view('admin.equivalencias.index', [
            'equivalencias' => AcademicText::cleanCollection(DB::table('equiv')
                ->join('asignatura as pca', 'pca.id', '=', 'equiv.asg_pca')
                ->leftJoin('asignatura as ext', function ($join) {
                    $join->on('ext.cod', '=', 'equiv.asg_ext')
                        ->orOn('ext.id', '=', 'equiv.asg_ext');
                })
                ->leftJoin('programa as pr_pca', 'pr_pca.cod', '=', 'pca.programa')
                ->leftJoin('programa as pr_ext', 'pr_ext.cod', '=', 'ext.programa')
                ->leftJoin('plan as plan_pca', 'plan_pca.id', '=', 'pca.plan')
                ->leftJoin('plan as plan_ext', 'plan_ext.id', '=', 'ext.plan')
                ->leftJoin('institucion', 'institucion.id', '=', 'pr_ext.inst')
                ->orderBy('equiv.id')
                ->select(
                    'equiv.*',
                    'pca.cod as asignatura_pca_cod',
                    'pca.nombre as asignatura_pca',
                    'pr_pca.nombre as programa_pca',
                    'plan_pca.num as plan_pca_num',
                    'ext.cod as asignatura_ext_cod',
                    'ext.nombre as asignatura_ext',
                    'pr_ext.nombre as programa_ext',
                    'pr_ext.enpca as programa_ext_enpca',
                    'plan_ext.num as plan_ext_num',
                    'institucion.nombre as institucion_nombre'
                )
                ->get(), [
                    'asignatura_pca',
                    'programa_pca',
                    'asignatura_ext',
                    'programa_ext',
                    'institucion_nombre',
                ]),
            'asignaturasPca' => $asignaturasPca,
            'asignaturasPensumOrigen' => $asignaturasPca,
            'asignaturasExternas' => $this->asignaturasExternas(),
        ]);
    }

    public function store(Request $request, AdminCatalogService $service)
    {
        $tipo = $request->input('tipo', 'externa');

        if ($tipo === 'reintegro') {
            $data = $request->validate([
                'mp' => ['required', 'integer', 'min:1'],
                'me' => ['required', 'integer', 'min:1'],
                'tipo' => ['required', 'in:reintegro'],
            ]);

            $destino = $this->findAsignaturaPca((int) $data['mp']);
            $origen = $this->findAsignaturaPca((int) $data['me']);

            if (!$destino || !$origen) {
                return back()->withErrors(['me' => 'Seleccione asignaturas institucionales validas.'])->withInput();
            }

            if ((int) $destino->id === (int) $origen->id) {
                return back()->withErrors(['me' => 'No se puede crear equivalencia entre la misma asignatura.'])->withInput();
            }

            if ((string) $destino->programa !== (string) $origen->programa) {
                return back()->withErrors(['me' => 'Para reintegro, las asignaturas deben pertenecer al mismo programa.'])->withInput();
            }

            if ((string) $destino->plan === (string) $origen->plan) {
                return back()->withErrors(['me' => 'Para reintegro, seleccione asignaturas de planes diferentes.'])->withInput();
            }

            $asgExt = (string) $origen->id;
            $auditAction = 'CREATE_EQUIVALENCIA_REINTEGRO';
        } else {
            $data = $request->validate([
                'mp' => ['required', 'integer', 'min:1'],
                'me' => ['required', 'string', 'max:20', 'not_in:0'],
                'tipo' => ['nullable', 'in:externa'],
            ]);

            $destino = $this->findAsignaturaPca((int) $data['mp']);
            $origen = $this->findAsignaturaExterna($data['me']);

            if (!$destino || !$origen) {
                return back()->withErrors(['me' => 'Seleccione asignaturas validas.'])->withInput();
            }

            $asgExt = $origen->cod;
            $auditAction = 'CREATE_EQUIVALENCIA';
        }

        $exists = DB::table('equiv')
            ->where('asg_pca', (string) $destino->id)
            ->where('asg_ext', $asgExt)
            ->exists();

        if ($exists) {
            return back()->withErrors(['me' => 'La equivalencia ya existe.'])->withInput();
        }

        DB::table('equiv')->insert([
            'asg_pca' => (string) $destino->id,
            'asg_ext' => $asgExt,
        ]);

        $service->audit($auditAction, [
            'asg_pca' => (string) $destino->id,
            'asg_ext' => $asgExt,
            'tipo' => $tipo,
        ]);

        return back()->with('status', 'Equivalencia creada correctamente.');
    }

    private function asignaturasPca()
    {
        $query = DB::table('asignatura')
            ->join('programa', 'programa.cod', '=', 'asignatura.programa')
            ->leftJoin('plan', 'plan.id', '=', 'asignatura.plan')
            ->where('programa.enpca', 1)
            ->orderBy('programa.nombre')
            ->orderBy('plan.num')
            ->orderBy('asignatura.nombre')
            ->select(
                'asignatura.*',
                'programa.nombre as programa_nombre',
                'plan.num as plan_num'
            );

        if (Schema::hasColumn('asignatura', 'activo')) {
            $query->where('asignatura.activo', 1);
        }

        return AcademicText::cleanCollection($query->get(), ['nombre', 'programa_nombre']);
    }

    private function asignaturasExternas()
    {
        $query = DB::table('asignatura')
            ->join('programa', 'programa.cod', '=', 'asignatura.programa')
            ->leftJoin('institucion', 'institucion.id', '=', 'programa.inst')
            ->where('programa.enpca', 0)
            ->orderBy('asignatura.nombre')
            ->select('asignatura.*', 'programa.nombre as programa_nombre', 'institucion.nombre as institucion_nombre');

        if (Schema::hasColumn('asignatura', 'activo')) {
            $query->where('asignatura.activo', 1);
        }

        return AcademicText::cleanCollection($query->get(), ['nombre', 'programa_nombre', 'institucion_nombre']);
    }

    private function findAsignaturaPca(int $id): ?object
    {
        return DB::table('asignatura')
            ->join('programa', 'programa.cod', '=', 'asignatura.programa')
            ->where('asignatura.id', $id)
            ->where('programa.enpca', 1)
            ->select('asignatura.*')
            ->first();
    }

    private function findAsignaturaExterna(string $codigo): ?object
    {
        return DB::table('asignatura')
            ->join('programa', 'programa.cod', '=', 'asignatura.programa')
            ->where('asignatura.cod', $codigo)
            ->where('programa.enpca', 0)
            ->select('asignatura.*')
            ->first();
    }
}
