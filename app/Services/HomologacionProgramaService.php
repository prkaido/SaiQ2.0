<?php

namespace App\Services;

use App\Support\AcademicText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class HomologacionProgramaService
{
    public function __construct(private AuditService $audit)
    {
    }

    public function formOptions(?object $draft = null): array
    {
        $programasUniversitarios = AcademicText::cleanCollection(DB::table('programa')
            ->where('enpca', 1)
            ->where('activo', 1)
            ->where('nivel', 3)
            ->orderBy('nombre')
            ->get(['cod', 'nombre']), ['nombre']);

        $relacionesCiclo = Schema::hasTable('progrel')
            ? DB::table('progrel as r')
                ->join('programa as p', 'p.cod', '=', 'r.prog')
                ->where('r.activo', 1)
                ->where('p.enpca', 1)
                ->where('p.activo', 1)
                ->where('p.nivel', 3)
                ->get(['r.dir', 'r.prog'])
            : collect();

        $relacionesProcedencia = Schema::hasTable('programa_procedencia_relacion')
            ? DB::table('programa_procedencia_relacion')
                ->where('activo', 1)
                ->get(['programa_pca_cod', 'programa_ext_cod'])
            : collect();

        return [
            'periodo' => DB::table('periodo')->where('activo', 1)->first(),
            'programasPca' => AcademicText::cleanCollection(DB::table('programa')
                ->where('enpca', 1)
                ->where('activo', 1)
                ->orderBy('nombre')
                ->get(), ['nombre']),
            'programasExternos' => $this->programasProcedencia(),
            'programasProcedencia' => $this->programasProcedenciaCategorizados(),
            'planes' => DB::table('plan')->orderBy('num')->get(),
            'programasUniversitarios' => $programasUniversitarios,
            'relacionesCiclo' => $relacionesCiclo,
            'relacionesProcedencia' => $relacionesProcedencia,
            'draftCicloUniversitario' => $draft ? $this->draftCicloUniversitario($draft) : null,
        ];
    }

    private function programasProcedencia()
    {
        $query = DB::table('programa')
            ->leftJoin('institucion', 'institucion.id', '=', 'programa.inst')
            ->where('programa.enpca', 0)
            ->orderBy('programa.nombre')
            ->select('programa.cod', 'programa.nombre', 'institucion.nombre as institucion_nombre');

        if (Schema::hasTable('programa_procedencia_relacion')) {
            $query->join('programa_procedencia_relacion as rel', function ($join) {
                $join->on('rel.programa_ext_cod', '=', 'programa.cod')
                    ->where('rel.activo', 1);
            })
                ->distinct();
        }

        return AcademicText::cleanCollection($query->get(), ['nombre', 'institucion_nombre']);
    }

    private function programasProcedenciaCategorizados()
    {
        if (!Schema::hasTable('programa_procedencia_relacion')) {
            return collect();
        }

        return AcademicText::cleanCollection(DB::table('programa_procedencia_relacion as rel')
            ->join('programa as ext', 'ext.cod', '=', 'rel.programa_ext_cod')
            ->join('programa as pca', 'pca.cod', '=', 'rel.programa_pca_cod')
            ->leftJoin('institucion', 'institucion.id', '=', 'ext.inst')
            ->where('rel.activo', 1)
            ->where('ext.enpca', 0)
            ->orderBy('pca.nombre')
            ->orderBy('ext.nombre')
            ->select([
                'ext.cod',
                'ext.nombre',
                'institucion.nombre as institucion_nombre',
                'rel.programa_pca_cod',
                'pca.nombre as programa_pca_nombre',
            ])
            ->get(), ['nombre', 'institucion_nombre', 'programa_pca_nombre']);
    }

    public function generate(array $data, string $userId): array
    {
        $data = $this->normalize($data);
        $this->validateProgramaProcedencia($data);
        $this->validateCicloUniversitario($data);

        $programaPca = $this->findPrograma($data['pca']);
        $programaExt = $this->findPrograma($data['ext']);
        $plan = DB::table('plan')->where('id', $data['pla'])->first();

        if (!$plan) {
            throw new RuntimeException('Plan no encontrado.');
        }

        $institucion = $this->findInstitucion($data['ext'], $programaExt->inst ?? null);
        $firma = DB::table('usuario')->where('id', $userId)->value('firma') ?: 'img/sin-firma.png';
        $equivalencias = $this->equivalencias($data['pca'], (int) $data['pla'], $data['ext']);

        $conEquivalencia = $equivalencias->where('tiene_equiv', 1)->values();
        $sinEquivalencia = $equivalencias->where('tiene_equiv', 0)->values();
        $esCorreccion = $this->esCorreccionHomologacion($data['homologacion_id'], $userId, 'programa');

        $homologacionId = $this->saveHomologacion($data, $userId, $institucion->id ?? null, $equivalencias, 'completado');
        $cicloUniversitario = $this->guardarCicloUniversitario($data, $userId, 'completado', $homologacionId);

        $this->audit->log('GENERAR_HOMOLOGACION_PROGRAMA', [
            'programa_pca' => $data['pca'],
            'programa_ext' => $data['ext'],
            'estudiante' => $data['ide'],
            'equivalencias' => $conEquivalencia->count(),
            'homologacion_ciclo_universitario_id' => $cicloUniversitario['homologacionId'] ?? null,
        ], $homologacionId);

        return [
            'data' => $data,
            'programaPca' => $programaPca,
            'programaExt' => $programaExt,
            'institucion' => $institucion,
            'plan' => $plan,
            'firma' => $firma,
            'conEquivalencia' => $conEquivalencia,
            'sinEquivalencia' => $sinEquivalencia,
            'homologacionId' => $homologacionId,
            'fecha' => now()->format('d/m/Y'),
            'cicloUniversitario' => $cicloUniversitario,
            'esCorreccion' => $esCorreccion,
        ];
    }

    public function saveDraft(array $data, string $userId): ?int
    {
        $data = $this->normalize($data);
        $this->validateProgramaProcedencia($data);
        $this->validateCicloUniversitario($data);
        $programaExt = $this->findPrograma($data['ext']);
        $institucion = $this->findInstitucion($data['ext'], $programaExt->inst ?? null);
        $equivalencias = $this->equivalencias($data['pca'], (int) $data['pla'], $data['ext']);

        $homologacionId = $this->saveHomologacion($data, $userId, $institucion->id ?? null, $equivalencias, 'borrador');
        $cicloUniversitario = $this->guardarCicloUniversitario($data, $userId, 'borrador', $homologacionId);

        $this->audit->log('GUARDAR_BORRADOR_PROGRAMA', [
            'programa_pca' => $data['pca'],
            'programa_ext' => $data['ext'],
            'estudiante' => $data['ide'],
            'equivalencias' => $equivalencias->where('tiene_equiv', 1)->count(),
            'homologacion_ciclo_universitario_id' => $cicloUniversitario['homologacionId'] ?? null,
        ], $homologacionId);

        return $homologacionId;
    }

    private function normalize(array $data): array
    {
        $data['tipo'] = $data['tipo'] ?? 'e';
        $data['obs'] = $data['obs'] ?? '';
        $data['semestre'] = $data['semestre'] ?? '';
        $data['homologacion_id'] = !empty($data['homologacion_id']) ? (int) $data['homologacion_id'] : null;
        $data['homologar_ciclo_universitario'] = filter_var($data['homologar_ciclo_universitario'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['pca_ciclo_universitario'] = $data['pca_ciclo_universitario'] ?? null;
        $data['pla_ciclo_universitario'] = !empty($data['pla_ciclo_universitario'])
            ? (int) $data['pla_ciclo_universitario']
            : null;

        return $data;
    }

    private function validateCicloUniversitario(array $data): void
    {
        if (!$data['homologar_ciclo_universitario']) {
            return;
        }

        if (empty($data['pca_ciclo_universitario'])) {
            throw new RuntimeException('Seleccione el programa del ciclo universitario.');
        }

        if (empty($data['pla_ciclo_universitario'])) {
            throw new RuntimeException('Seleccione el plan del ciclo universitario.');
        }

        $programa = $this->findPrograma($data['pca_ciclo_universitario']);

        if (
            (int) ($programa->enpca ?? 0) !== 1 ||
            (int) ($programa->activo ?? 0) !== 1 ||
            (int) ($programa->nivel ?? 0) !== 3
        ) {
            throw new RuntimeException('El programa del ciclo universitario no es un programa profesional activo de PCA.');
        }

        if (Schema::hasTable('progrel')) {
            $relacionados = DB::table('progrel as r')
                ->join('programa as p', 'p.cod', '=', 'r.prog')
                ->where('r.dir', $data['pca'])
                ->where('r.activo', 1)
                ->where('p.enpca', 1)
                ->where('p.activo', 1)
                ->where('p.nivel', 3)
                ->pluck('r.prog');

            if ($relacionados->isNotEmpty() && !$relacionados->contains($data['pca_ciclo_universitario'])) {
                throw new RuntimeException('El programa del ciclo universitario no esta relacionado con el programa seleccionado.');
            }
        }

        $plan = DB::table('plan')->where('id', $data['pla_ciclo_universitario'])->first();

        if (!$plan || (string) ($plan->programa ?? '') !== (string) $data['pca_ciclo_universitario']) {
            throw new RuntimeException('El plan del ciclo universitario no corresponde al programa seleccionado.');
        }
    }

    private function validateProgramaProcedencia(array $data): void
    {
        if (!Schema::hasTable('programa_procedencia_relacion')) {
            return;
        }

        $relacionados = DB::table('programa_procedencia_relacion')
            ->where('programa_pca_cod', $data['pca'])
            ->where('activo', 1)
            ->pluck('programa_ext_cod');

        if ($relacionados->isNotEmpty() && !$relacionados->contains($data['ext'])) {
            throw new RuntimeException('El programa de procedencia no esta relacionado con el programa institucional seleccionado.');
        }
    }

    private function guardarCicloUniversitario(array $data, string $userId, string $estado, ?int $homologacionOrigenId): ?array
    {
        if (!$data['homologar_ciclo_universitario'] || !$homologacionOrigenId) {
            return null;
        }

        $cicloData = $data;
        $cicloData['pca'] = $data['pca_ciclo_universitario'];
        $cicloData['pla'] = $data['pla_ciclo_universitario'];
        $cicloData['homologacion_id'] = $this->existingCicloUniversitarioId($homologacionOrigenId);
        $esCorreccion = $this->esCorreccionHomologacion($cicloData['homologacion_id'], $userId, 'programa');

        $programaPca = $this->findPrograma($cicloData['pca']);
        $programaExt = $this->findPrograma($cicloData['ext']);
        $plan = DB::table('plan')->where('id', $cicloData['pla'])->first();

        if (!$plan) {
            throw new RuntimeException('Plan del ciclo universitario no encontrado.');
        }

        $institucion = $this->findInstitucion($cicloData['ext'], $programaExt->inst ?? null);
        $firma = DB::table('usuario')->where('id', $userId)->value('firma') ?: 'img/sin-firma.png';
        $equivalencias = $this->equivalencias($cicloData['pca'], (int) $cicloData['pla'], $cicloData['ext']);

        $homologacionId = $this->saveHomologacion(
            $cicloData,
            $userId,
            $institucion->id ?? null,
            $equivalencias,
            $estado,
            [
                'ciclo_universitario' => true,
                'homologacion_origen_id' => $homologacionOrigenId,
                'homologacion_id' => $cicloData['homologacion_id'],
            ]
        );

        $conEquivalencia = $equivalencias->where('tiene_equiv', 1)->values();
        $sinEquivalencia = $equivalencias->where('tiene_equiv', 0)->values();

        $this->audit->log($estado === 'completado' ? 'GENERAR_HOMOLOGACION_CICLO_UNIVERSITARIO' : 'GUARDAR_BORRADOR_CICLO_UNIVERSITARIO', [
            'homologacion_origen_id' => $homologacionOrigenId,
            'programa_pca' => $cicloData['pca'],
            'programa_ext' => $cicloData['ext'],
            'estudiante' => $cicloData['ide'],
            'equivalencias' => $conEquivalencia->count(),
        ], $homologacionId);

        return [
            'data' => $cicloData,
            'programaPca' => $programaPca,
            'programaExt' => $programaExt,
            'institucion' => $institucion,
            'plan' => $plan,
            'firma' => $firma,
            'conEquivalencia' => $conEquivalencia,
            'sinEquivalencia' => $sinEquivalencia,
            'homologacionId' => $homologacionId,
            'fecha' => now()->format('d/m/Y'),
            'esCicloUniversitario' => true,
            'esCorreccion' => $esCorreccion,
        ];
    }

    private function existingCicloUniversitarioId(int $homologacionOrigenId): ?int
    {
        if (
            !Schema::hasTable('homologacion') ||
            !Schema::hasColumn('homologacion', 'homologacion_origen_id') ||
            !Schema::hasColumn('homologacion', 'ciclo_universitario')
        ) {
            return null;
        }

        $id = DB::table('homologacion')
            ->where('homologacion_origen_id', $homologacionOrigenId)
            ->where('ciclo_universitario', 1)
            ->value('id');

        return $id ? (int) $id : null;
    }

    private function draftCicloUniversitario(object $draft): ?object
    {
        if (
            !Schema::hasTable('homologacion') ||
            !Schema::hasColumn('homologacion', 'homologacion_origen_id') ||
            !Schema::hasColumn('homologacion', 'ciclo_universitario')
        ) {
            return null;
        }

        return DB::table('homologacion')
            ->where('homologacion_origen_id', $draft->id)
            ->where('ciclo_universitario', 1)
            ->first();
    }

    private function findPrograma(string $codigo): object
    {
        $programa = DB::table('programa')->where('cod', $codigo)->first();

        if (!$programa) {
            throw new RuntimeException('Programa no encontrado: ' . $codigo);
        }

        return AcademicText::cleanObject($programa, ['nombre']);
    }

    private function findInstitucion(string $programaExt, ?string $institucionId): ?object
    {
        $abrev = substr($programaExt, 0, 2);
        $institucion = DB::table('institucion')->where('abrev', $abrev)->first();

        if (!$institucion && $institucionId) {
            $institucion = DB::table('institucion')->where('id', $institucionId)->first();
        }

        return AcademicText::cleanObject($institucion, ['nombre']);
    }

    private function equivalencias(string $pca, int $plan, string $ext)
    {
        $equivalencias = DB::table('asignatura as a')
            ->leftJoin('semestre as s', 's.id', '=', 'a.nivel')
            ->leftJoin('equiv as e', function ($join) use ($ext) {
                $join->on('e.asg_pca', '=', 'a.id')
                    ->whereRaw('SUBSTR(e.asg_ext, 1, 5) = ?', [substr($ext, 0, 5)]);
            })
            ->leftJoin('asignatura as ae', 'ae.cod', '=', 'e.asg_ext')
            ->where('a.programa', $pca)
            ->where('a.plan', $plan)
            ->orderBy('a.nivel')
            ->orderBy('a.nombre')
            ->select([
                'a.id as id_pca',
                'a.cod as cod_pca',
                'a.nombre as nombre_pca',
                'a.creditos',
                's.romano as semestre',
                'e.asg_ext as cod_ext',
                'ae.nombre as nombre_ext',
                'ae.creditos as creditos_ext',
            ])
            ->selectRaw('CASE WHEN e.asg_ext IS NULL THEN 0 ELSE 1 END as tiene_equiv')
            ->get();

        return AcademicText::cleanCollection($equivalencias, ['nombre_pca', 'nombre_ext']);
    }

    private function saveHomologacion(
        array $data,
        string $userId,
        ?int $institucionId,
        $equivalencias,
        string $estado,
        array $metadata = []
    ): ?int
    {
        if (!Schema::hasTable('homologacion')) {
            return null;
        }

        try {
            $columns = Schema::getColumnListing('homologacion');
            $payload = [
                'estudiante_nom' => $data['nom'],
                'estudiante_ape' => $data['ape'],
                'estudiante_id' => $data['ide'],
                'periodo' => $data['per'],
            ];

            if (in_array('user_id', $columns, true)) {
                $payload['user_id'] = $userId;
            }
            if (in_array('tipo', $columns, true)) {
                $payload['tipo'] = 'programa';
            }
            if (in_array('programa_pca_cod', $columns, true)) {
                $payload['programa_pca_cod'] = $data['pca'];
                $payload['plan_id'] = $data['pla'];
                $payload['programa_ext_cod'] = $data['ext'];
                if (in_array('programa_ext_nombre', $columns, true)) {
                    $payload['programa_ext_nombre'] = null;
                }
                $payload['institucion_id'] = $institucionId;
                $payload['tipo_estudio'] = $data['tipo'];
                if (in_array('ciclo_universitario', $columns, true)) {
                    $payload['ciclo_universitario'] = (bool) ($metadata['ciclo_universitario'] ?? false);
                }
                $payload['semestre'] = $data['semestre'];
                $payload['observaciones'] = $data['obs'];
                $payload['estado'] = $estado;
                if (in_array('homologacion_origen_id', $columns, true)) {
                    $payload['homologacion_origen_id'] = $metadata['homologacion_origen_id'] ?? null;
                }
            } else {
                $payload['programa_pca'] = $data['pca'];
                $payload['plan_pca'] = $data['pla'];
                $payload['programa_ext'] = $data['ext'];
                $payload['institucion_ext'] = $institucionId;
            }

            if (in_array('updated_at', $columns, true)) {
                $payload['updated_at'] = now();
            }

            $existingId = (int) ($metadata['homologacion_id'] ?? $data['homologacion_id'] ?? 0);
            if ($existingId > 0) {
                $existing = $this->editableHomologacion($existingId, $userId, 'programa');
                if (!$existing) {
                    throw new RuntimeException('Homologacion no disponible para edicion.');
                }

                $before = $this->snapshot($existing);
                $finalEstado = $this->estadoParaActualizacion($existing, $estado);
                $payload['estado'] = $finalEstado;
                if (in_array('ciclo_universitario', $columns, true) && !array_key_exists('ciclo_universitario', $metadata)) {
                    $payload['ciclo_universitario'] = (bool) ($existing->ciclo_universitario ?? false);
                }
                if (in_array('homologacion_origen_id', $columns, true) && !array_key_exists('homologacion_origen_id', $metadata)) {
                    $payload['homologacion_origen_id'] = $existing->homologacion_origen_id ?? null;
                }

                DB::table('homologacion')
                    ->where('id', $existingId)
                    ->update(array_intersect_key($payload, array_flip($columns)));

                if (Schema::hasTable('homologacion_detalle')) {
                    DB::table('homologacion_detalle')->where('homologacion_id', $existingId)->delete();
                }
                $this->saveDetalle($existingId, $equivalencias);

                $updated = DB::table('homologacion')->where('id', $existingId)->first();
                $this->audit->log(
                    $this->accionActualizacion($existing, $finalEstado),
                    [
                        'programa_pca' => $data['pca'],
                        'programa_ext' => $data['ext'],
                        'estudiante' => $data['ide'],
                    ],
                    $existingId,
                    'homologacion',
                    $before,
                    $updated ? $this->snapshot($updated) : null
                );

                return $existingId;
            }

            $homologacionId = (int) DB::table('homologacion')->insertGetId($payload);
            $this->saveDetalle($homologacionId, $equivalencias);

            return $homologacionId;
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function saveDetalle(int $homologacionId, $equivalencias): void
    {
        if (!Schema::hasTable('homologacion_detalle')) {
            return;
        }

        $rows = [];
        foreach ($equivalencias->values() as $index => $item) {
            $rows[] = [
                'homologacion_id' => $homologacionId,
                'asignatura_pca_cod' => $item->cod_pca,
                'asignatura_pca_nombre' => $item->nombre_pca,
                'asignatura_ext_nombre' => $item->nombre_ext,
                'semestre' => $item->semestre,
                'creditos' => $item->creditos,
                'nota' => $item->tiene_equiv ? '4.5' : null,
                'tiene_equivalencia' => $item->tiene_equiv,
                'orden' => $index,
            ];
        }

        if ($rows) {
            DB::table('homologacion_detalle')->insert($rows);
        }
    }

    private function editableHomologacion(int $id, string $userId, string $tipo): ?object
    {
        $homologacion = DB::table('homologacion')->where('id', $id)->first();

        if (!$homologacion || !in_array(($homologacion->estado ?? null), ['borrador', 'completado'], true)) {
            return null;
        }

        if (($homologacion->tipo ?? $tipo) !== $tipo) {
            return null;
        }

        if ((int) session('tus') !== 1 && ($homologacion->user_id ?? null) !== $userId) {
            return null;
        }

        return $homologacion;
    }

    private function esCorreccionHomologacion(?int $id, string $userId, string $tipo): bool
    {
        if (!$id || !Schema::hasTable('homologacion')) {
            return false;
        }

        $homologacion = $this->editableHomologacion($id, $userId, $tipo);

        return $homologacion && ($homologacion->estado ?? null) === 'completado';
    }

    private function estadoParaActualizacion(object $homologacion, string $estadoSolicitado): string
    {
        if (($homologacion->estado ?? null) === 'completado') {
            return 'completado';
        }

        return $estadoSolicitado;
    }

    private function accionActualizacion(object $homologacion, string $estadoFinal): string
    {
        if (($homologacion->estado ?? null) === 'completado') {
            return 'ACTUALIZAR_HOMOLOGACION_PROGRAMA';
        }

        if ($estadoFinal === 'completado') {
            return 'COMPLETAR_BORRADOR_PROGRAMA';
        }

        return 'ACTUALIZAR_BORRADOR_PROGRAMA';
    }

    private function snapshot(object $homologacion): array
    {
        return [
            'estudiante_nom' => $homologacion->estudiante_nom ?? null,
            'estudiante_ape' => $homologacion->estudiante_ape ?? null,
            'estudiante_id' => $homologacion->estudiante_id ?? null,
            'periodo' => $homologacion->periodo ?? null,
            'programa_pca_cod' => $homologacion->programa_pca_cod ?? null,
            'plan_id' => $homologacion->plan_id ?? null,
            'programa_ext_cod' => $homologacion->programa_ext_cod ?? null,
            'institucion_id' => $homologacion->institucion_id ?? null,
            'tipo_estudio' => $homologacion->tipo_estudio ?? null,
            'ciclo_universitario' => $homologacion->ciclo_universitario ?? null,
            'semestre' => $homologacion->semestre ?? null,
            'observaciones' => $homologacion->observaciones ?? null,
            'estado' => $homologacion->estado ?? null,
            'homologacion_origen_id' => $homologacion->homologacion_origen_id ?? null,
        ];
    }

}
