<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('programa') ||
            !Schema::hasTable('plan') ||
            !Schema::hasTable('asignatura')
        ) {
            return;
        }

        DB::transaction(function () {
            $this->seedNiveles();
            $this->seedSemestres();
            $this->seedProgramas();

            $planTecnologia = $this->upsertPlan('613', '61301', 61301);
            $planProfesional = $this->upsertPlan('614', '61401', 61401);

            foreach ($this->asignaturasTecnologia() as $asignatura) {
                $this->upsertAsignatura($asignatura, $planTecnologia);
            }

            foreach ($this->asignaturasProfesional() as $asignatura) {
                $this->upsertAsignatura($asignatura, $planProfesional);
            }

            $this->seedRelacionCiclo();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('progrel')) {
            DB::table('progrel')->where('dir', '613')->where('prog', '614')->delete();
        }

        $planIds = collect();
        if (Schema::hasTable('plan')) {
            $planIds = DB::table('plan')
                ->whereIn('programa', ['613', '614'])
                ->whereIn('num', ['61301', '61401'])
                ->pluck('id');
        }

        if (Schema::hasTable('asignatura')) {
            $codes = collect(array_merge($this->asignaturasTecnologia(), $this->asignaturasProfesional()))
                ->pluck('cod')
                ->all();

            $query = DB::table('asignatura')
                ->whereIn('programa', ['613', '614'])
                ->whereIn('cod', $codes);

            if ($planIds->isNotEmpty()) {
                $query->whereIn('plan', $planIds);
            }

            $query->delete();
        }

        if (Schema::hasTable('plan')) {
            DB::table('plan')->whereIn('programa', ['613', '614'])->whereIn('num', ['61301', '61401'])->delete();
        }

        if (Schema::hasTable('programa')) {
            foreach (['613', '614'] as $programa) {
                $hasAsignaturas = Schema::hasTable('asignatura') && DB::table('asignatura')->where('programa', $programa)->exists();
                $hasPlanes = Schema::hasTable('plan') && DB::table('plan')->where('programa', $programa)->exists();

                if (!$hasAsignaturas && !$hasPlanes) {
                    DB::table('programa')->where('cod', $programa)->delete();
                }
            }
        }
    }

    private function seedNiveles(): void
    {
        if (!Schema::hasTable('nivel')) {
            return;
        }

        foreach ([
            ['id' => 2, 'descripcion' => 'Tecnologico'],
            ['id' => 3, 'descripcion' => 'Profesional'],
        ] as $nivel) {
            $this->upsertRow('nivel', ['id' => $nivel['id']], [
                'descripcion' => $nivel['descripcion'],
                'romano' => null,
                'tipo' => 'Programa',
                'activo' => 1,
            ]);
        }
    }

    private function seedSemestres(): void
    {
        if (!Schema::hasTable('semestre')) {
            return;
        }

        foreach ([
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
        ] as $id => $romano) {
            $this->upsertRow('semestre', ['id' => $id], ['romano' => $romano]);
        }
    }

    private function seedProgramas(): void
    {
        foreach ([
            ['cod' => '613', 'nombre' => 'TECNOLOGIA EN GESTION HUMANA', 'nivel' => 2],
            ['cod' => '614', 'nombre' => 'ADMINISTRACION DEL TALENTO HUMANO', 'nivel' => 3],
        ] as $programa) {
            $this->upsertRow('programa', ['cod' => $programa['cod']], [
                'nombre' => $programa['nombre'],
                'nivel' => $programa['nivel'],
                'inst' => null,
                'enpca' => 1,
                'activo' => 1,
            ]);
        }
    }

    private function upsertPlan(string $programa, string $numero, int $preferredId): int
    {
        $existing = DB::table('plan')
            ->where('programa', $programa)
            ->where('num', $numero)
            ->first();

        $payload = $this->onlyColumns('plan', [
            'programa' => $programa,
            'num' => $numero,
            'vigencia' => 2015,
        ]);

        if ($existing) {
            DB::table('plan')->where('id', $existing->id)->update($payload);

            return (int) $existing->id;
        }

        if (Schema::hasColumn('plan', 'id') && !DB::table('plan')->where('id', $preferredId)->exists()) {
            $payload['id'] = $preferredId;
        }

        DB::table('plan')->insert($payload);

        return (int) DB::table('plan')
            ->where('programa', $programa)
            ->where('num', $numero)
            ->value('id');
    }

    private function upsertAsignatura(array $asignatura, int $planId): void
    {
        $this->upsertRow(
            'asignatura',
            [
                'cod' => $asignatura['cod'],
                'programa' => $asignatura['programa'],
                'plan' => $planId,
            ],
            [
                'codigo' => $asignatura['abreviacion'],
                'nombre' => $asignatura['nombre'],
                'nivel' => $asignatura['nivel'],
                'creditos' => (string) $asignatura['creditos'],
                'ihsemana' => $asignatura['ih_semana'],
                'activo' => 1,
            ]
        );
    }

    private function seedRelacionCiclo(): void
    {
        if (!Schema::hasTable('progrel')) {
            return;
        }

        $this->upsertRow('progrel', ['dir' => '613', 'prog' => '614'], ['activo' => 1]);
    }

    private function upsertRow(string $table, array $where, array $payload): void
    {
        $where = $this->onlyColumns($table, $where);
        $payload = $this->onlyColumns($table, $payload);
        $exists = $this->where(DB::table($table), $where)->exists();

        if ($exists) {
            $payload = $this->withTimestamps($table, $payload, false);
            if ($payload) {
                $this->where(DB::table($table), $where)->update($payload);
            }

            return;
        }

        DB::table($table)->insert($this->withTimestamps($table, $where + $payload, true));
    }

    private function where($query, array $where)
    {
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }

        return $query;
    }

    private function withTimestamps(string $table, array $payload, bool $creating): array
    {
        $now = now();

        if ($creating && Schema::hasColumn($table, 'created_at')) {
            $payload['created_at'] = $now;
        }

        if (Schema::hasColumn($table, 'updated_at')) {
            $payload['updated_at'] = $now;
        }

        return $payload;
    }

    private function onlyColumns(string $table, array $payload): array
    {
        return array_intersect_key($payload, array_flip(Schema::getColumnListing($table)));
    }

    private function asignaturasTecnologia(): array
    {
        return [
            ['cod' => '000007', 'nombre' => 'INGLES I', 'abreviacion' => 'INGL I', 'programa' => '613', 'nivel' => 1, 'creditos' => 2, 'ih_semana' => 4],
            ['cod' => '001038', 'nombre' => 'COMPETENCIAS COMUNICATIVAS I', 'abreviacion' => 'COMP COMUN', 'programa' => '613', 'nivel' => 1, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '001059', 'nombre' => 'CIUDADANIA Y DEMOCRACIA', 'abreviacion' => 'CIU Y DEM', 'programa' => '613', 'nivel' => 1, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '004048', 'nombre' => 'MATEMATICAS APLICADAS', 'abreviacion' => 'MAT APLIC', 'programa' => '613', 'nivel' => 1, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '613001', 'nombre' => 'PENSAMIENTO ADMINISTRATIVO', 'abreviacion' => 'PENS ADM', 'programa' => '613', 'nivel' => 1, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613002', 'nombre' => 'INTRODUCCION A LA GESTION DEL TALENTO HUMANO', 'abreviacion' => 'INT GTH', 'programa' => '613', 'nivel' => 1, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '000008', 'nombre' => 'INGLES II', 'abreviacion' => 'INGLES II', 'programa' => '613', 'nivel' => 2, 'creditos' => 2, 'ih_semana' => 4],
            ['cod' => '001039', 'nombre' => 'COMPETENCIAS COMUNICATIVAS II', 'abreviacion' => 'COMP COM II', 'programa' => '613', 'nivel' => 2, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '004006', 'nombre' => 'ESTADISTICA DESCRIPTIVA', 'abreviacion' => 'ESTADDESCRI', 'programa' => '613', 'nivel' => 2, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613003', 'nombre' => 'PSICOLOGIA ORGANIZACIONAL', 'abreviacion' => 'PSIC AORG', 'programa' => '613', 'nivel' => 2, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613004', 'nombre' => 'PROCESOS ADMINISTRATIVOS', 'abreviacion' => 'PROC ADM', 'programa' => '613', 'nivel' => 2, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613005', 'nombre' => 'SISTEMAS DE ESTRUCTURACION DE CARGO Y SELECCION DE PERSONAL', 'abreviacion' => 'SIST EST CARG', 'programa' => '613', 'nivel' => 2, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '001060', 'nombre' => 'PRINCIPIOS DE AXIOLOGIA', 'abreviacion' => 'PRINC AXIO', 'programa' => '613', 'nivel' => 3, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '004009', 'nombre' => 'ESTADISTICA INFERENCIAL', 'abreviacion' => 'ESTADI INFER', 'programa' => '613', 'nivel' => 3, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613006', 'nombre' => 'CONTABILIDAD FINANCIERA', 'abreviacion' => 'CONTAB FINAN', 'programa' => '613', 'nivel' => 3, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613007', 'nombre' => 'COMUNICACION Y CULTURA ORGANIZACIONAL', 'abreviacion' => 'COM Y CUL ORG', 'programa' => '613', 'nivel' => 3, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '613008', 'nombre' => 'FUNDAMENTOS DE ECONOMIA', 'abreviacion' => 'FUND DE ECON', 'programa' => '613', 'nivel' => 3, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613009', 'nombre' => 'ELECTIVA DE PROFUNDIZACION I', 'abreviacion' => 'ELEC PROF I', 'programa' => '613', 'nivel' => 3, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '613010', 'nombre' => 'DERECHO LABORAL INDIVIDUAL', 'abreviacion' => 'DEREC LAB IND', 'programa' => '613', 'nivel' => 4, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '613011', 'nombre' => 'CAPACITACION Y DESARROLLO DE PERSONAL', 'abreviacion' => 'CAPAC Y DESARR', 'programa' => '613', 'nivel' => 4, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '613012', 'nombre' => 'FUNDAMENTOS DE MERCADEO', 'abreviacion' => 'FUND DE MERC', 'programa' => '613', 'nivel' => 4, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '613013', 'nombre' => 'COSTOS Y PRESUPUESTO', 'abreviacion' => 'COST Y PRES', 'programa' => '613', 'nivel' => 4, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613014', 'nombre' => 'COMPENSACION Y SEGURIDAD SOCIAL', 'abreviacion' => 'COMP Y SEG', 'programa' => '613', 'nivel' => 4, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '001027', 'nombre' => 'METODOLOGIA DE LA INVESTIGACION', 'abreviacion' => 'MET DE LA INVES', 'programa' => '613', 'nivel' => 5, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '613015', 'nombre' => 'SISTEMA DE GESTION DE SEGURIDAD Y SALUD EN EL TRABAJO', 'abreviacion' => 'SGSST', 'programa' => '613', 'nivel' => 5, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613016', 'nombre' => 'EMPRENDIMIENTO', 'abreviacion' => 'EMPREND', 'programa' => '613', 'nivel' => 5, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '613017', 'nombre' => 'EVALUACION DEL DESEMPENO', 'abreviacion' => 'EVAL DESEM', 'programa' => '613', 'nivel' => 5, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613018', 'nombre' => 'ADMINISTRACION FINANCIERA', 'abreviacion' => 'ADMON FINAC', 'programa' => '613', 'nivel' => 5, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613019', 'nombre' => 'ELECTIVA COMPLEMENTARIA I', 'abreviacion' => 'ELEC COM I', 'programa' => '613', 'nivel' => 5, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '613700', 'nombre' => 'OPCION DE GRADO TECNOLOGICA', 'abreviacion' => 'OPC GRADO TEC', 'programa' => '613', 'nivel' => 5, 'creditos' => 0, 'ih_semana' => 0],
        ];
    }

    private function asignaturasProfesional(): array
    {
        return [
            ['cod' => '001115', 'nombre' => 'PROYECTO DE INVESTIGACION', 'abreviacion' => 'PROYECTO INVEST', 'programa' => '614', 'nivel' => 6, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '614001', 'nombre' => 'BIENESTAR ORGANIZACIONAL', 'abreviacion' => 'BIENET ORG', 'programa' => '614', 'nivel' => 6, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '614002', 'nombre' => 'TEORIA DE LAS ORGANIZACIONES', 'abreviacion' => 'TEOR ORG', 'programa' => '614', 'nivel' => 6, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '614003', 'nombre' => 'FINANZAS CORPORATIVAS', 'abreviacion' => 'FINANZ CORP', 'programa' => '614', 'nivel' => 6, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '614004', 'nombre' => 'ELECTIVA COMPLEMENTARIA II', 'abreviacion' => 'ELEC COM II', 'programa' => '614', 'nivel' => 6, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '001045', 'nombre' => 'ELECTIVA DE HUMANIDADES', 'abreviacion' => 'ELEC HUMAN', 'programa' => '614', 'nivel' => 7, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '614005', 'nombre' => 'GERENCIA ESTRATEGICA DEL CAPITAL HUMANO', 'abreviacion' => 'GER EST CALID', 'programa' => '614', 'nivel' => 7, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '614006', 'nombre' => 'COMPORTAMIENTO ORGANIZACIONAL', 'abreviacion' => 'COMP ORG', 'programa' => '614', 'nivel' => 7, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '614007', 'nombre' => 'GESTION POR COMPETENCIAS', 'abreviacion' => 'GESTION COMP', 'programa' => '614', 'nivel' => 7, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '614008', 'nombre' => 'DERECHO LABORAL COLECTIVO', 'abreviacion' => 'DEREC LAB COL', 'programa' => '614', 'nivel' => 7, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '001043', 'nombre' => 'NEGOCIACION Y SOLUCION DE CONFLICTOS', 'abreviacion' => 'NE Y SOL CONF', 'programa' => '614', 'nivel' => 8, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '614009', 'nombre' => 'SISTEMAS DE INFORMACION GERENCIAL', 'abreviacion' => 'SIS INF GER', 'programa' => '614', 'nivel' => 8, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '614010', 'nombre' => 'GERENCIA DE LA FELICIDAD', 'abreviacion' => 'GERE FELIC', 'programa' => '614', 'nivel' => 8, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '614011', 'nombre' => 'EQUIPO DE ALTO DESEMPENO', 'abreviacion' => 'EQU ALTO DESEM', 'programa' => '614', 'nivel' => 8, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '614012', 'nombre' => 'ELECTIVA DE PROFUNDIZACION II', 'abreviacion' => 'ELEC PROF II', 'programa' => '614', 'nivel' => 8, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '614013', 'nombre' => 'GERENCIA DE CALIDAD', 'abreviacion' => 'GERE DE CAL', 'programa' => '614', 'nivel' => 8, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '001053', 'nombre' => 'ETICA PROFESIONAL', 'abreviacion' => 'ETICA PROFESI', 'programa' => '614', 'nivel' => 9, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '614014', 'nombre' => 'FORMULACION Y EVALUACION DE PROYECTOS', 'abreviacion' => 'FORM Y EVAL PRO', 'programa' => '614', 'nivel' => 9, 'creditos' => 3, 'ih_semana' => 3],
            ['cod' => '614015', 'nombre' => 'PROYECTO EMPRESARIAL', 'abreviacion' => 'PROY EMPR', 'programa' => '614', 'nivel' => 9, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '614016', 'nombre' => 'GESTION DEL CONOCIMIENTO', 'abreviacion' => 'GEST CONOC', 'programa' => '614', 'nivel' => 9, 'creditos' => 2, 'ih_semana' => 2],
            ['cod' => '614017', 'nombre' => 'AUDITORIA DE GESTION DE TALENTO HUMANO', 'abreviacion' => 'AUD GEST TAL', 'programa' => '614', 'nivel' => 9, 'creditos' => 4, 'ih_semana' => 4],
            ['cod' => '614300', 'nombre' => 'OPCION DE GRADO UNIVERSITARIA', 'abreviacion' => 'OPC GRADOUNIV', 'programa' => '614', 'nivel' => 9, 'creditos' => 0, 'ih_semana' => 0],
        ];
    }
};
