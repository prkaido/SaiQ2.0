<?php

namespace App\Services;

use App\Support\AcademicText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class HomologacionAsignaturaService
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

        return [
            'periodo' => DB::table('periodo')->where('activo', 1)->first(),
            'programasPca' => AcademicText::cleanCollection(DB::table('programa')
                ->where('enpca', 1)
                ->where('activo', 1)
                ->orderBy('nombre')
                ->get(), ['nombre']),
            'planes' => DB::table('plan')->orderBy('num')->get(),
            'instituciones' => AcademicText::cleanCollection(DB::table('institucion')->orderBy('nombre')->get(), ['nombre']),
            'institucionPca' => $this->institucionPca(),
            'programasUniversitarios' => $programasUniversitarios,
            'relacionesCiclo' => $relacionesCiclo,
            'draftCicloUniversitario' => $draft ? $this->draftCicloUniversitario($draft) : null,
        ];
    }

    public function reviewData(array $data, string $userId): array
    {
        $data = $this->normalizeInitial($data);
        $this->validateCicloUniversitario($data);

        $programaPca = $this->programa($data['pca']);
        $plan = $this->plan((int) $data['pla']);
        $institucion = $this->institucion((int) $data['ins']);
        $asignaturas = $this->asignaturasPorPlan($data['pca'], (int) $data['pla']);
        $equivalenciasInternas = $data['cambio_pensum']
            ? $this->equivalenciasInternas($data['pca'], (int) $data['pla'])
            : collect();

        $homologacionId = $this->saveDraft($data, $userId, $asignaturas);
        $cicloUniversitario = $this->saveCicloUniversitarioDraft($data, $userId, $homologacionId);

        return [
            'data' => $data,
            'programaPca' => $programaPca,
            'plan' => $plan,
            'institucion' => $institucion,
            'asignaturas' => $asignaturas,
            'equivalenciasInternas' => $equivalenciasInternas,
            'homologacionId' => $homologacionId,
            'cicloUniversitario' => $cicloUniversitario,
        ];
    }

    public function saveInitialDraft(array $data, string $userId): ?int
    {
        $data = $this->normalizeInitial($data);
        $this->validateCicloUniversitario($data);

        $asignaturas = $this->asignaturasPorPlan($data['pca'], (int) $data['pla']);

        $homologacionId = $this->saveDraft($data, $userId, $asignaturas);
        $this->saveCicloUniversitarioDraft($data, $userId, $homologacionId);

        return $homologacionId;
    }

    public function resultData(array $data, array $post, string $userId): array
    {
        $data['pex'] = AcademicText::name($data['pex'] ?? '');
        $approved = [];
        $pending = [];
        $originNames = [];
        $approvedCiclo = [];
        $pendingCiclo = [];
        $originNamesCiclo = [];

        foreach ($post as $key => $value) {
            if (preg_match('/^n(\d+)$/', $key, $matches)) {
                $id = (int) $matches[1];
                $notaRaw = str_replace(',', '.', trim((string) $value));
                $nota = filter_var($notaRaw, FILTER_VALIDATE_FLOAT, [
                    'options' => ['min_range' => 0, 'max_range' => 5],
                ]);

                if ($nota !== false) {
                    $approved[$id] = $nota;
                } else {
                    $pending[$id] = true;
                }
            }

            if (preg_match('/^t(\d+)$/', $key, $matches)) {
                $originNames[(int) $matches[1]] = AcademicText::name((string) $value);
            }

            if (preg_match('/^cn(\d+)$/', $key, $matches)) {
                $id = (int) $matches[1];
                $notaRaw = str_replace(',', '.', trim((string) $value));
                $nota = filter_var($notaRaw, FILTER_VALIDATE_FLOAT, [
                    'options' => ['min_range' => 0, 'max_range' => 5],
                ]);

                if ($nota !== false) {
                    $approvedCiclo[$id] = $nota;
                } else {
                    $pendingCiclo[$id] = true;
                }
            }

            if (preg_match('/^ct(\d+)$/', $key, $matches)) {
                $originNamesCiclo[(int) $matches[1]] = AcademicText::name((string) $value);
            }
        }

        foreach (array_keys($originNames) as $id) {
            if (!isset($approved[$id])) {
                $pending[$id] = true;
            }
        }

        foreach (array_keys($originNamesCiclo) as $id) {
            if (!isset($approvedCiclo[$id])) {
                $pendingCiclo[$id] = true;
            }
        }

        $homologacionId = isset($data['homologacion_id']) ? (int) $data['homologacion_id'] : null;
        $esCorreccion = $this->esCorreccionHomologacion($homologacionId, $userId, 'asignatura');
        if ($homologacionId > 0) {
            $this->completeDraft($homologacionId, $data, $approved, $pending, $originNames, $userId);
        }

        $cicloUniversitario = null;
        $homologacionCicloId = isset($data['homologacion_ciclo_universitario_id'])
            ? (int) $data['homologacion_ciclo_universitario_id']
            : null;
        $esCorreccionCiclo = $this->esCorreccionHomologacion($homologacionCicloId, $userId, 'asignatura');

        if ($homologacionCicloId > 0 && !empty($data['pca_ciclo_universitario']) && !empty($data['pla_ciclo_universitario'])) {
            $cicloData = $this->resultDataCicloUniversitario($data);
            $this->completeDraft($homologacionCicloId, $cicloData, $approvedCiclo, $pendingCiclo, $originNamesCiclo, $userId);

            $cicloUniversitario = [
                'data' => $cicloData,
                'programaPca' => $this->programa($cicloData['pca']),
                'plan' => $this->plan((int) $cicloData['pla']),
                'institucion' => $this->institucion((int) $cicloData['ins']),
                'firma' => DB::table('usuario')->where('id', $userId)->value('firma') ?: 'img/default_firma.png',
                'approved' => $approvedCiclo,
                'originNames' => $originNamesCiclo,
                'asignaturasAprobadas' => $this->asignaturasPorIds(array_keys($approvedCiclo)),
                'asignaturasPendientes' => $this->asignaturasPorIds(array_keys($pendingCiclo)),
                'fecha' => now()->format('d/m/Y'),
                'esCicloUniversitario' => true,
                'esCorreccion' => $esCorreccionCiclo,
            ];
        }

        return [
            'data' => $data,
            'programaPca' => $this->programa($data['pca']),
            'plan' => $this->plan((int) $data['pla']),
            'institucion' => $this->institucion((int) $data['ins']),
            'firma' => DB::table('usuario')->where('id', $userId)->value('firma') ?: 'img/default_firma.png',
            'approved' => $approved,
            'originNames' => $originNames,
            'asignaturasAprobadas' => $this->asignaturasPorIds(array_keys($approved)),
            'asignaturasPendientes' => $this->asignaturasPorIds(array_keys($pending)),
            'fecha' => now()->format('d/m/Y'),
            'cicloUniversitario' => $cicloUniversitario,
            'esCorreccion' => $esCorreccion,
        ];
    }

    private function normalizeInitial(array $data): array
    {
        $data['homologacion_id'] = !empty($data['homologacion_id']) ? (int) $data['homologacion_id'] : null;
        $data['cambio_pensum'] = filter_var($data['cambio_pensum'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['ext'] = AcademicText::name($data['ext'] ?? '');
        if ($data['cambio_pensum']) {
            $programa = !empty($data['pca']) ? $this->programa($data['pca']) : null;
            $data['ext'] = 'Cambio de pensum' . ($programa ? ' - ' . $programa->nombre : '');
            $data['ins'] = !empty($data['ins']) ? (int) $data['ins'] : $this->institucionPcaId();
        }
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

        $programa = $this->programa($data['pca_ciclo_universitario']);

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

        $plan = $this->plan((int) $data['pla_ciclo_universitario']);

        if ((string) ($plan->programa ?? '') !== (string) $data['pca_ciclo_universitario']) {
            throw new RuntimeException('El plan del ciclo universitario no corresponde al programa seleccionado.');
        }
    }

    private function saveCicloUniversitarioDraft(array $data, string $userId, ?int $homologacionOrigenId): ?array
    {
        if (!$data['homologar_ciclo_universitario'] || !$homologacionOrigenId) {
            return null;
        }

        $cicloData = $data;
        $cicloData['pca'] = $data['pca_ciclo_universitario'];
        $cicloData['pla'] = $data['pla_ciclo_universitario'];
        $cicloData['homologacion_id'] = $this->existingCicloUniversitarioId($homologacionOrigenId);

        $asignaturas = $this->asignaturasPorPlan($cicloData['pca'], (int) $cicloData['pla']);
        $homologacionId = $this->saveDraft($cicloData, $userId, $asignaturas, [
            'ciclo_universitario' => true,
            'homologacion_origen_id' => $homologacionOrigenId,
            'homologacion_id' => $cicloData['homologacion_id'],
        ]);

        return [
            'data' => $cicloData,
            'programaPca' => $this->programa($cicloData['pca']),
            'plan' => $this->plan((int) $cicloData['pla']),
            'asignaturas' => $asignaturas,
            'equivalenciasInternas' => $cicloData['cambio_pensum']
                ? $this->equivalenciasInternas($cicloData['pca'], (int) $cicloData['pla'])
                : collect(),
            'homologacionId' => $homologacionId,
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

    private function resultDataCicloUniversitario(array $data): array
    {
        $cicloData = $data;
        $cicloData['pca'] = $data['pca_ciclo_universitario'];
        $cicloData['pla'] = $data['pla_ciclo_universitario'];
        $cicloData['homologacion_id'] = $data['homologacion_ciclo_universitario_id'] ?? null;

        return $cicloData;
    }

    private function programa(string $codigo): object
    {
        $programa = DB::table('programa')->where('cod', $codigo)->first();

        if (!$programa) {
            throw new RuntimeException('Programa no encontrado.');
        }

        return AcademicText::cleanObject($programa, ['nombre']);
    }

    private function plan(int $id): object
    {
        $plan = DB::table('plan')->where('id', $id)->first();

        if (!$plan) {
            throw new RuntimeException('Plan no encontrado.');
        }

        return $plan;
    }

    private function institucion(int $id): object
    {
        $institucion = DB::table('institucion')->where('id', $id)->first();

        if (!$institucion) {
            throw new RuntimeException('Institucion no encontrada.');
        }

        return AcademicText::cleanObject($institucion, ['nombre']);
    }

    private function institucionPca(): ?object
    {
        $institucion = DB::table('institucion')
            ->where('nombre', 'like', '%POLITECNICO COSTA ATLANTICA%')
            ->orWhere('nombre', 'like', '%POLITÉCNICO COSTA ATLÁNTICA%')
            ->orWhereIn('abrev', ['PC', 'PCA'])
            ->orderBy('id')
            ->first();

        if (!$institucion) {
            $institucion = DB::table('institucion')->orderBy('id')->first();
        }

        return AcademicText::cleanObject($institucion, ['nombre']);
    }

    private function institucionPcaId(): int
    {
        $institucion = $this->institucionPca();

        if (!$institucion) {
            throw new RuntimeException('No fue posible identificar la institucion PCA para cambio de pensum.');
        }

        return (int) $institucion->id;
    }

    private function asignaturasPorPlan(string $programa, int $plan)
    {
        $asignaturas = DB::table('asignatura as a')
            ->leftJoin('semestre as s', 's.id', '=', 'a.nivel')
            ->where('a.programa', $programa)
            ->where('a.plan', $plan)
            ->orderBy('a.nivel')
            ->orderBy('a.cod')
            ->select('a.*', 's.romano')
            ->get();

        return AcademicText::cleanCollection($asignaturas, ['nombre']);
    }

    private function equivalenciasInternas(string $programa, int $plan)
    {
        $destinos = DB::table('asignatura')
            ->where('programa', $programa)
            ->where('plan', $plan)
            ->pluck('id')
            ->map(fn ($id) => (string) $id);

        if ($destinos->isEmpty() || !Schema::hasTable('equiv')) {
            return collect();
        }

        $map = [];
        $equivalencias = DB::table('equiv')
            ->whereIn('asg_pca', $destinos)
            ->get(['asg_pca', 'asg_ext']);

        foreach ($equivalencias as $equivalencia) {
            $origen = $this->asignaturaInternaOrigen((string) $equivalencia->asg_ext, $programa, $plan);

            if ($origen) {
                AcademicText::cleanObject($origen, ['nombre']);
                $map[(int) $equivalencia->asg_pca] = (object) [
                    'id' => $origen->id,
                    'cod' => $origen->cod,
                    'nombre' => $origen->nombre,
                    'plan' => $origen->plan,
                    'plan_num' => $origen->plan_num,
                    'nota' => '4.5',
                ];
            }
        }

        return collect($map);
    }

    private function asignaturaInternaOrigen(string $value, string $programa, int $plan): ?object
    {
        if (ctype_digit($value)) {
            $origen = $this->buscarAsignaturaInternaOrigen('a.id', (int) $value, $programa, $plan);

            if ($origen) {
                return $origen;
            }
        }

        return $this->buscarAsignaturaInternaOrigen('a.cod', $value, $programa, $plan);
    }

    private function buscarAsignaturaInternaOrigen(string $column, int|string $value, string $programa, int $plan): ?object
    {
        return DB::table('asignatura as a')
            ->join('programa as p', 'p.cod', '=', 'a.programa')
            ->leftJoin('plan as pl', 'pl.id', '=', 'a.plan')
            ->where($column, $value)
            ->where('a.programa', $programa)
            ->where('a.plan', '<>', $plan)
            ->where('p.enpca', 1)
            ->select('a.*', 'pl.num as plan_num')
            ->first();
    }

    private function asignaturasPorIds(array $ids)
    {
        if (!$ids) {
            return collect();
        }

        $asignaturas = DB::table('asignatura as a')
            ->leftJoin('semestre as s', 's.id', '=', 'a.nivel')
            ->whereIn('a.id', $ids)
            ->orderBy('a.nivel')
            ->orderBy('a.cod')
            ->select('a.*', 's.romano')
            ->get();

        return AcademicText::cleanCollection($asignaturas, ['nombre']);
    }

    private function saveDraft(array $data, string $userId, $asignaturas, array $metadata = []): ?int
    {
        if (!Schema::hasTable('homologacion')) {
            return null;
        }

        try {
            $columns = Schema::getColumnListing('homologacion');
            if (!in_array('programa_pca_cod', $columns, true)) {
                return null;
            }

            $payload = [
                'user_id' => $userId,
                'tipo' => 'asignatura',
                'estudiante_nom' => $data['nom'],
                'estudiante_ape' => $data['ape'],
                'estudiante_id' => $data['ide'],
                'periodo' => $data['per'],
                'programa_pca_cod' => $data['pca'],
                'plan_id' => $data['pla'],
                'institucion_id' => $data['ins'],
                'tipo_estudio' => !empty($data['cambio_pensum']) ? 'r' : 'e',
                'estado' => 'borrador',
            ];

            if (in_array('ciclo_universitario', $columns, true)) {
                $payload['ciclo_universitario'] = (bool) ($metadata['ciclo_universitario'] ?? false);
            }
            if (in_array('homologacion_origen_id', $columns, true)) {
                $payload['homologacion_origen_id'] = $metadata['homologacion_origen_id'] ?? null;
            }

            if (in_array('programa_ext_nombre', $columns, true)) {
                $payload['programa_ext_nombre'] = $data['ext'];
                $payload['programa_ext_cod'] = null;
            } else {
                $payload['programa_ext_cod'] = substr($data['ext'], 0, 10);
            }

            if (in_array('updated_at', $columns, true)) {
                $payload['updated_at'] = now();
            }

            $existingId = (int) ($metadata['homologacion_id'] ?? $data['homologacion_id'] ?? 0);
            if ($existingId > 0) {
                $existing = $this->editableHomologacion($existingId, $userId, 'asignatura');
                if (!$existing) {
                    throw new RuntimeException('Homologacion no disponible para edicion.');
                }

                $before = $this->snapshot($existing);
                $payload['estado'] = $this->estadoParaActualizacion($existing, 'borrador');
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
                $this->saveInitialDetalle($existingId, $asignaturas);

                $updated = DB::table('homologacion')->where('id', $existingId)->first();
                $this->audit->log($this->accionActualizacion($existing, $payload['estado']), [
                    'programa_pca' => $data['pca'],
                    'programa_ext' => $data['ext'],
                    'estudiante' => $data['ide'],
                    'asignaturas' => $asignaturas->count(),
                ], $existingId, 'homologacion', $before, $updated ? $this->snapshot($updated) : null);

                return $existingId;
            }

            $id = DB::table('homologacion')->insertGetId($payload);
            $this->saveInitialDetalle((int) $id, $asignaturas);

            $this->audit->log(!empty($metadata['ciclo_universitario']) ? 'GUARDAR_BORRADOR_CICLO_ASIGNATURA' : 'GUARDAR_BORRADOR_ASIGNATURA', [
                'programa_pca' => $data['pca'],
                'programa_ext' => $data['ext'],
                'estudiante' => $data['ide'],
                'asignaturas' => $asignaturas->count(),
                'homologacion_origen_id' => $metadata['homologacion_origen_id'] ?? null,
            ], (int) $id);

            return (int) $id;
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            // El guardado de borrador no debe impedir generar el formato.
            return null;
        }
    }

    private function saveInitialDetalle(int $homologacionId, $asignaturas): void
    {
        if (!Schema::hasTable('homologacion_detalle')) {
            return;
        }

        $rows = [];
        foreach ($asignaturas->values() as $index => $asignatura) {
            $rows[] = [
                'homologacion_id' => $homologacionId,
                'asignatura_pca_cod' => $asignatura->cod,
                'asignatura_pca_nombre' => $asignatura->nombre,
                'asignatura_ext_nombre' => '',
                'semestre' => $asignatura->romano,
                'creditos' => $asignatura->creditos,
                'nota' => null,
                'tiene_equivalencia' => 0,
                'orden' => $index,
            ];
        }

        if ($rows) {
            DB::table('homologacion_detalle')->insert($rows);
        }
    }

    private function completeDraft(int $homologacionId, array $data, array $approved, array $pending, array $originNames, string $userId): void
    {
        if (!Schema::hasTable('homologacion')) {
            return;
        }

        try {
            $homologacion = DB::table('homologacion')->where('id', $homologacionId)->first();
            if (!$homologacion || ((int) session('tus') !== 1 && $homologacion->user_id !== $userId)) {
                return;
            }

            if (($homologacion->tipo ?? 'asignatura') !== 'asignatura') {
                return;
            }

            $before = [
                'estado' => $homologacion->estado ?? null,
                'tipo_estudio' => $homologacion->tipo_estudio ?? null,
            ];
            $after = [
                'estado' => 'completado',
                'tipo_estudio' => $this->tipoEstudioFromTip((int) $data['tip']),
            ];

            $columns = Schema::getColumnListing('homologacion');
            $payload = [
                'tipo_estudio' => $after['tipo_estudio'],
                'semestre' => $data['semestre'] ?? '',
                'observaciones' => $data['obs'] ?? '',
                'estado' => 'completado',
            ];

            if (in_array('updated_at', $columns, true)) {
                $payload['updated_at'] = now();
            }

            DB::table('homologacion')
                ->where('id', $homologacionId)
                ->update(array_intersect_key($payload, array_flip($columns)));

            if (Schema::hasTable('homologacion_detalle')) {
                DB::table('homologacion_detalle')->where('homologacion_id', $homologacionId)->delete();
                $rows = [];
                foreach ($this->asignaturasPorPlan($data['pca'], (int) $data['pla'])->values() as $index => $asignatura) {
                    $rows[] = [
                        'homologacion_id' => $homologacionId,
                        'asignatura_pca_cod' => $asignatura->cod,
                        'asignatura_pca_nombre' => $asignatura->nombre,
                        'asignatura_ext_nombre' => substr($originNames[$asignatura->id] ?? '', 0, 250),
                        'semestre' => $asignatura->romano,
                        'creditos' => $asignatura->creditos,
                        'nota' => isset($approved[$asignatura->id]) ? (string) round($approved[$asignatura->id], 1) : null,
                        'tiene_equivalencia' => isset($approved[$asignatura->id]) ? 1 : 0,
                        'orden' => $index,
                    ];
                }
                if ($rows) {
                    DB::table('homologacion_detalle')->insert($rows);
                }
            }

            $this->audit->log(
                ($homologacion->estado ?? null) === 'completado'
                    ? 'ACTUALIZAR_HOMOLOGACION_ASIGNATURA'
                    : 'COMPLETAR_HOMOLOGACION_ASIGNATURA',
                [
                'estudiante' => $data['ide'],
                'aprobadas' => count($approved),
                'pendientes' => count($pending),
                ],
                $homologacionId,
                'homologacion',
                $before,
                $after
            );
        } catch (\Throwable $exception) {
            // La actualizacion de trazabilidad no debe bloquear la generacion del formato.
        }
    }

    private function tipoEstudioFromTip(int $tip): string
    {
        return match ($tip) {
            1 => 'i',
            2 => 'r',
            default => 'e',
        };
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
            return 'ACTUALIZAR_HOMOLOGACION_ASIGNATURA';
        }

        if ($estadoFinal === 'completado') {
            return 'COMPLETAR_HOMOLOGACION_ASIGNATURA';
        }

        return 'ACTUALIZAR_BORRADOR_ASIGNATURA';
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
            'programa_ext_nombre' => $homologacion->programa_ext_nombre ?? null,
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
