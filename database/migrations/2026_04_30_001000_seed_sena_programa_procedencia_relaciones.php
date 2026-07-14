<?php

use App\Support\AcademicText;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('programa') || !Schema::hasTable('institucion')) {
            return;
        }

        if (!Schema::hasTable('programa_procedencia_relacion')) {
            Schema::create('programa_procedencia_relacion', function (Blueprint $table) {
                $table->charset = 'utf8mb4';
                $table->collation = 'utf8mb4_unicode_ci';
                $table->id();
                $table->string('programa_ext_cod', 10);
                $table->string('programa_pca_cod', 10);
                $table->unsignedInteger('institucion_id')->nullable();
                $table->string('nivel_origen', 20)->nullable();
                $table->integer('activo')->default(1);
                $table->timestamps();
                $table->unique(['programa_ext_cod', 'programa_pca_cod'], 'programa_proc_ext_pca_unique');
                $table->index('programa_pca_cod', 'programa_proc_pca_idx');
                $table->index('programa_ext_cod', 'programa_proc_ext_idx');
            });
        }

        DB::transaction(function () {
            $senaId = $this->senaInstitucionId();
            $this->seedProgramasInstitucionales();

            $generatedCodes = [];
            foreach ($this->matriz() as $index => $row) {
                [$origen, $nivel, $destino] = $row;
                $destinoCod = $this->destinos()[$this->key($destino)] ?? null;

                if (!$destinoCod) {
                    continue;
                }

                $origen = $this->cleanOrigen($origen);
                $codigoOrigen = $this->codigoOrigen($origen, $index, $generatedCodes);

                $this->upsertPrograma($codigoOrigen, [
                    'nombre' => $origen,
                    'nivel' => $this->nivelId($nivel),
                    'inst' => (string) $senaId,
                    'enpca' => 0,
                    'activo' => 1,
                ]);

                $this->upsertRelacion($codigoOrigen, $destinoCod, $senaId, $nivel);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('programa_procedencia_relacion')) {
            DB::table('programa_procedencia_relacion')->delete();
        }

        if (Schema::hasTable('programa')) {
            DB::table('programa')->where('cod', 'like', 'SN%')->delete();
        }
    }

    private function senaInstitucionId(): int
    {
        $sena = DB::table('institucion')->where('abrev', 'SE')->first();
        $payload = $this->onlyColumns('institucion', [
            'nombre' => 'SENA',
            'abrev' => 'SE',
            'tipo' => 'IES',
            'activo' => 1,
        ]);

        if ($sena) {
            DB::table('institucion')->where('id', $sena->id)->update($this->withTimestamps('institucion', $payload, false));

            return (int) $sena->id;
        }

        if (Schema::hasColumn('institucion', 'id') && !DB::table('institucion')->where('id', 2)->exists()) {
            DB::table('institucion')->insert($this->withTimestamps('institucion', ['id' => 2] + $payload, true));

            return 2;
        }

        return (int) DB::table('institucion')->insertGetId($this->withTimestamps('institucion', $payload, true));
    }

    private function seedProgramasInstitucionales(): void
    {
        foreach ($this->programasInstitucionales() as $cod => $programa) {
            $this->upsertPrograma($cod, [
                'nombre' => $programa['nombre'],
                'nivel' => $programa['nivel'],
                'inst' => '1',
                'enpca' => 1,
                'activo' => 1,
            ]);
        }
    }

    private function upsertPrograma(string $cod, array $payload): void
    {
        $payload = $this->onlyColumns('programa', $payload);
        $exists = DB::table('programa')->where('cod', $cod)->exists();

        if ($exists) {
            DB::table('programa')->where('cod', $cod)->update($this->withTimestamps('programa', $payload, false));

            return;
        }

        DB::table('programa')->insert($this->withTimestamps('programa', ['cod' => $cod] + $payload, true));
    }

    private function upsertRelacion(string $origenCod, string $destinoCod, int $senaId, string $nivel): void
    {
        $payload = [
            'institucion_id' => $senaId,
            'nivel_origen' => $this->nivelNombre($nivel),
            'activo' => 1,
        ];

        $exists = DB::table('programa_procedencia_relacion')
            ->where('programa_ext_cod', $origenCod)
            ->where('programa_pca_cod', $destinoCod)
            ->exists();

        if ($exists) {
            DB::table('programa_procedencia_relacion')
                ->where('programa_ext_cod', $origenCod)
                ->where('programa_pca_cod', $destinoCod)
                ->update($this->withTimestamps('programa_procedencia_relacion', $payload, false));

            return;
        }

        DB::table('programa_procedencia_relacion')->insert($this->withTimestamps('programa_procedencia_relacion', [
            'programa_ext_cod' => $origenCod,
            'programa_pca_cod' => $destinoCod,
        ] + $payload, true));
    }

    private function codigoOrigen(string $origen, int $index, array &$generatedCodes): string
    {
        $key = $this->key($origen);
        $known = $this->codigosOrigenConocidos();

        if (isset($known[$key])) {
            return $known[$key];
        }

        if (!isset($generatedCodes[$key])) {
            $generatedCodes[$key] = 'SN' . str_pad((string) (count($generatedCodes) + 1), 3, '0', STR_PAD_LEFT);
        }

        return $generatedCodes[$key];
    }

    private function cleanOrigen(string $origen): string
    {
        $origen = AcademicText::upper($origen);
        $origen = str_replace([' .', '  '], ['.', ' '], $origen);
        $origen = rtrim($origen, " .\t\n\r\0\x0B");

        return match ($this->key($origen)) {
            'GESTION LOIGISTICA' => 'GESTION LOGISTICA',
            'GESTION DE LA SEGURIDAD Y SALUD EN EL TRABJO' => 'GESTION DE LA SEGURIDAD Y SALUD EN EL TRABAJO',
            default => $origen,
        };
    }

    private function nivelId(string $nivel): int
    {
        return $this->key($nivel) === 'TECNICO' ? 1 : 2;
    }

    private function nivelNombre(string $nivel): string
    {
        return $this->nivelId($nivel) === 1 ? 'TECNICO' : 'TECNOLOGICO';
    }

    private function key(string $value): string
    {
        $value = AcademicText::upper($value);
        $value = strtr($value, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);
        $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
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

    private function destinos(): array
    {
        return [
            'ADMINISTRACION DE EMPRESAS' => '604',
            'ADMINISTRACION DE MERCADEO' => '804',
            'ADMINISTRACION DE TALENTO HUMANO' => '614',
            'ADMINISTRACION DEL TALENTO HUMANO' => '614',
            'PUBLICIDAD' => '808',
            'TECNOLOGIA EN PROCESOS INDUSTRIALES' => '503',
            'TECNOLOGIA EN GESTION FINANCIERA' => '601',
            'TECNOLOGIA EN GESTION HUMANA' => '613',
            'TECNOLOGIA EN DESARROLLO DE SISTEMAS ELECTRONICOS' => '911',
            'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD' => '809',
            'TECNOLOGIA EN GESTION DE PUBLICIDAD Y MEDIOS DIGITALES' => '807',
            'INGENIERIA ELECTRONICA' => '904',
            'INGENIERIA DE SISTEMAS' => '504',
            'TECNOLOGIA EN DESARROLLO DE SOFTWARE' => '502',
            'TECNOLOGIA EN GESTION Y ANALITICA DE DATOS' => '515',
            'TECNOLOGIA EN GESTION CONTABLE' => '605',
            'SEGURIDAD Y SALUD EN EL TRABAJO' => '514',
            'TECNOLOGIA EN GESTION DE NEGOCIOS INTERNACIONALES' => '611',
            'ADMINISTRACION DE NEGOCIOS INTERNACIONALES' => '608',
            'INGENIERIA DE SISTEMAS ARTICULADO POR CICLOS CON TECNOLOGIA EN DESARROLLO DE SOFTWARE' => '504',
            'INGENIERIA EN CIENCIA DE DATOS ARTICULADO POR CICLOS CON TECNOLOGIA EN GESTION Y ANALITICA DE DATOS' => '516',
            'TECNOLOGIA EN GESTION LOGISTICA INTERNACIONAL' => '609',
            'ADMINISTRACION LOGISTICA' => '610',
        ];
    }

    private function programasInstitucionales(): array
    {
        return [
            '502' => ['nombre' => 'TECNOLOGIA EN DESARROLLO DE SOFTWARE', 'nivel' => 2],
            '503' => ['nombre' => 'TECNOLOGIA EN PROCESOS INDUSTRIALES', 'nivel' => 2],
            '504' => ['nombre' => 'INGENIERIA DE SISTEMAS', 'nivel' => 3],
            '506' => ['nombre' => 'INGENIERIA INDUSTRIAL', 'nivel' => 3],
            '513' => ['nombre' => 'TECNOLOGIA EN GESTION DE SEGURIDAD Y SALUD EN EL TRABAJO', 'nivel' => 2],
            '514' => ['nombre' => 'SEGURIDAD Y SALUD EN EL TRABAJO', 'nivel' => 3],
            '515' => ['nombre' => 'TECNOLOGIA EN GESTION Y ANALITICA DE DATOS', 'nivel' => 2],
            '516' => ['nombre' => 'INGENIERIA EN CIENCIA DE DATOS', 'nivel' => 3],
            '601' => ['nombre' => 'TECNOLOGIA EN GESTION FINANCIERA', 'nivel' => 2],
            '604' => ['nombre' => 'ADMINISTRACION DE EMPRESAS', 'nivel' => 3],
            '605' => ['nombre' => 'TECNOLOGIA EN GESTION CONTABLE', 'nivel' => 2],
            '608' => ['nombre' => 'ADMINISTRACION DE NEGOCIOS INTERNACIONALES', 'nivel' => 3],
            '609' => ['nombre' => 'TECNOLOGIA EN GESTION LOGISTICA INTERNACIONAL', 'nivel' => 2],
            '610' => ['nombre' => 'ADMINISTRACION LOGISTICA', 'nivel' => 3],
            '611' => ['nombre' => 'TECNOLOGIA EN GESTION DE NEGOCIOS INTERNACIONALES', 'nivel' => 2],
            '613' => ['nombre' => 'TECNOLOGIA EN GESTION HUMANA', 'nivel' => 2],
            '614' => ['nombre' => 'ADMINISTRACION DEL TALENTO HUMANO', 'nivel' => 3],
            '804' => ['nombre' => 'ADMINISTRACION DE MERCADEO', 'nivel' => 3],
            '807' => ['nombre' => 'TECNOLOGIA EN GESTION DE PUBLICIDAD Y MEDIOS DIGITALES', 'nivel' => 2],
            '808' => ['nombre' => 'PUBLICIDAD', 'nivel' => 3],
            '809' => ['nombre' => 'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD', 'nivel' => 2],
            '904' => ['nombre' => 'INGENIERIA ELECTRONICA', 'nivel' => 3],
            '911' => ['nombre' => 'TECNOLOGIA EN DESARROLLO DE SISTEMAS ELECTRONICOS', 'nivel' => 2],
        ];
    }

    private function codigosOrigenConocidos(): array
    {
        return [
            'TECNOLOGIA EN GESTION EMPRESARIAL' => 'SE023',
            'GESTION ADMINISTRATIVA' => 'SE021',
            'GESTION BANCARIA Y DE ENTIDADES FINANCIERAS' => 'SE008',
            'GESTION CONTABLE Y FINANCIERA' => 'SE013',
            'GESTION DE NEGOCIOS' => 'SE022',
            'GESTION CONTABLE Y DE INFORMACION FINANCIERA' => 'SE031',
            'DIRECCION DE VENTAS' => 'SE012',
            'GESTION DE MERCADOS' => 'SE014',
            'ASISTENCIA ADMINISTRATIVA' => 'SE009',
            'GESTION LOGISTICA' => 'SE011',
            'COORDINACION DE PROCESOS LOGISTICOS' => 'SE029',
            'AUTOMATIZACION INDUSTRIAL' => 'SE006',
            'GESTION DE LA PRODUCCION INDUSTRIAL' => 'SE018',
            'SALUD OCUPACIONAL' => 'SE020',
            'DESARROLLO DE OPERACIONES LOGISTICA EN LA CADENA DE ABASTECIMIENTO' => 'SE015',
            'NEGOCIACION INTERNACIONAL' => 'SE024',
            'DESARROLLO DE SISTEMAS ELECTRONICOS INDUSTRIALES' => 'SE017',
        ];
    }

    private function matriz(): array
    {
        return [
            ['TECNOLOGIA EN GESTION EMPRESARIAL', 'TECNOLOGICO', 'ADMINISTRACION DE EMPRESAS'],
            ['GESTIÓN ADMINISTRATIVA', 'TECNOLOGICO', 'ADMINISTRACION DE EMPRESAS'],
            ['GESTIÓN BANCARIA Y DE ENTIDADES FINANCIERAS', 'TECNOLOGICO', 'ADMINISTRACION DE EMPRESAS'],
            ['GESTION CONTABLE Y FINANCIERA', 'TECNOLOGICO', 'ADMINISTRACION DE EMPRESAS'],
            ['GESTIÓN DE NEGOCIOS', 'TECNOLOGICO', 'ADMINISTRACION DE EMPRESAS'],
            ['GESTIÓN DEL TALENTO HUMANO', 'TECNOLOGICO', 'ADMINISTRACION DE EMPRESAS'],
            ['GESTION CONTABLE Y DE INFORMACION FINANCIERA', 'TECNOLOGICO', 'ADMINISTRACION DE EMPRESAS'],
            ['DIRECCION DE VENTAS', 'TECNOLOGICO', 'ADMINISTRACIÓN DE MERCADEO'],
            ['GESTION DE MERCADOS', 'TECNOLOGICO', 'ADMINISTRACIÓN DE MERCADEO'],
            ['DESARROLLO DE PROCESOS DE MERCADEO', 'TECNOLOGICO', 'ADMINISTRACIÓN DE MERCADEO'],
            ['GESTIÓN DEL TALENTO HUMANO', 'TECNOLOGICO', 'ADMINISTRACION DE TALENTO HUMANO'],
            ['DIRECCION DE VENTAS', 'TECNOLOGICO', 'PUBLICIDAD'],
            ['GESTION DE MERCADOS', 'TECNOLOGICO', 'PUBLICIDAD'],
            ['DESARROLLO DE MEDIOS GRAFICOS VISUALES', 'TECNOLOGICO', 'PUBLICIDAD'],
            ['PRODUCCIÓN DE MULTIMEDIA', 'TECNOLOGICO', 'PUBLICIDAD'],
            ['SUPERVISION DE PROCESOS DE CONFECCION', 'TECNOLOGICO', 'TECNOLOGIA EN PROCESOS INDUSTRIALES'],
            ['DESARROLLO PUBLICITARIO', 'TECNOLOGICO', 'PUBLICIDAD'],
            ['ASISTENCIA ADMINISTRATIVA', 'TECNICO', 'TECNOLOGIA EN GESTION FINANCIERA'],
            ['ASISTENCIA EN ORGANIZACIÓN DE ARCHIVOS', 'TECNICO', 'TECNOLOGIA EN GESTION FINANCIERA'],
            ['CONTABILIZACIÓN DE OPERACIONES COMERCIALES Y FINANCIERAS', 'TECNICO', 'TECNOLOGIA EN GESTION FINANCIERA'],
            ['EL RIESGO CREDITICIO Y SU ADMINISTRACIÓN', 'TECNICO', 'TECNOLOGIA EN GESTION FINANCIERA'],
            ['RECURSOS HUMANOS', 'TECNICO', 'TECNOLOGIA EN GESTION HUMANA'],
            ['MTTO Y ENSAMBLE DE EQUIPOS ELECTRONICOS', 'TECNICO', 'TECNOLOGIA EN DESARROLLO DE SISTEMAS ELECTRONICOS'],
            ['GESTIÓN DOCUMENTAL', 'TECNOLOGICO', 'TECNOLOGIA EN GESTION FINANCIERA'],
            ['SERVICIOS Y OPERACIONES MICROFINANCIERAS', 'TECNICO', 'TECNOLOGIA EN GESTION FINANCIERA'],
            ['NOMINA Y PRESTACIONES SOCIALES', 'TECNICO', 'TECNOLOGIA EN GESTION HUMANA'],
            ['PRESELECCION DE TALENTO HUMANO MEDIADO POR HERRAMIENTAS TIC', 'TECNICO', 'TECNOLOGIA EN GESTION HUMANA'],
            ['ASESORIA COMERCIAL', 'TECNICO', 'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD'],
            ['GESTION COMERCIAL Y TELEMERCADEO EN CONTAC CENTER', 'TECNICO', 'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD'],
            ['INFORMACION Y SERVICIO AL CLIENTE', 'TECNICO', 'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD'],
            ['OPERACION DE SERVICIOS EN CONTACT CENTER Y BPO', 'TECNICO', 'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD'],
            ['OPERACIONES COMERCIALES EN ALMACENES DE CADENA', 'TECNICO', 'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD'],
            ['COMERCIALIZACION DE PRODUCTOS MASIVOS', 'TECNICO', 'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD'],
            ['ATENCION INTEGRAL AL CLIENTE', 'TECNICO', 'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD'],
            ['VENTA DE PRODUCTOS EN LINEA', 'TECNICO', 'TECNOLOGIA EN GESTION DE MERCADEO Y PUBLICIDAD'],
            ['INTEGRACION DE SERVICIOS DIGITALES', 'TECNICO', 'TECNOLOGÍA EN GESTIÓN DE PUBLICIDAD Y MEDIOS DIGITALES'],
            ['DISEÑO E INTEGRACION DE MULTIMEDIA', 'TECNOLOGICO', 'PUBLICIDAD'],
            ['DESARROLLO DE SISTEMAS ELECTRONICOS INDUSTRIALES', 'TECNOLOGICO', 'INGENIERIA ELECTRÓNICA'],
            ['ANÁLISIS Y DESARROLLO DE SOFTWARE', 'TECNOLOGICO', 'INGENIERÍA DE SISTEMAS'],
            ['GESTIÓN DE REDES DE DATOS', 'TECNOLOGICO', 'TECNOLOGÍA EN DESARROLLO DE SOFTWARE'],
            ['SISTEMAS TELEINFORMÁTICOS', 'TÉCNICO', 'TECNOLOGÍA EN DESARROLLO DE SOFTWARE'],
            ['PROGRAMACIÓN DE SOFTWARE', 'TÉCNICO', 'TECNOLOGÍA EN DESARROLLO DE SOFTWARE'],
            ['PROGRAMACIÓN PARA ANALÍTICA DE DATOS', 'TÉCNICO', 'TECNOLOGÍA EN GESTIÓN Y ANALÍTICA DE DATOS'],
            ['CONTABILIDAD Y FINANZAS', 'TECNOLOGICO', 'TECNOLOGIA EN GESTION CONTABLE'],
            ['CONTABILIZACION DE OPERACIONES CONTABLE FINANCIERA', 'TÉCNICO', 'TECNOLOGIA EN GESTION CONTABLE'],
            ['GESTION CONTABLE Y DE INFORMACION FINANCIERA', 'TECNOLOGICO', 'TECNOLOGIA EN GESTION CONTABLE'],
            ['GESTION LOGISTICA', 'TECNOLOGICO', 'TECNOLOGIA EN PROCESOS INDUSTRIALES'],
            ['GESTION ADMINISTRATIVA SECTOR SALUD', 'TECNOLOGICO', 'TECNOLOGIA EN GESTION DE LA SEGURIDAD Y SALUD EN EL TRABAJO'],
            ['GESTIÓN INTEGRADA DE LA CALIDAD, MEDIO AMBIENTE, SEGURIDAD Y SALUD', 'TECNOLOGICO', 'TECNOLOGIA EN PROCESOS INDUSTRIALES'],
            ['GESTIÓN INTEGRADA DE LA CALIDAD, MEDIO AMBIENTE, SEGURIDAD Y SALUD', 'TECNOLOGICO', 'SEGURIDAD Y SALUD EN EL TRABAJO'],
            ['COORDINACION DE PROCESOS LOGISTICOS', 'TECNOLOGICO', 'TECNOLOGIA EN PROCESOS INDUSTRIALES'],
            ['AUTOMATIZACIÓN INDUSTRIAL', 'TECNOLOGICO', 'TECNOLOGIA EN PROCESOS INDUSTRIALES'],
            ['GESTIÓN DE LA PRODUCCIÓN INDUSTRIAL', 'TECNOLOGICO', 'INGENIERIA INDUSTRIAL'],
            ['MANTENIMIENTO ELECTROMECÁNICO INDUSTRIAL', 'TECNOLOGICO', 'TECNOLOGIA EN PROCESOS INDUSTRIALES'],
            ['GESTION DE LA SEGURIDAD Y SALUD EN EL TRABAJO', 'TECNOLOGICO', 'SEGURIDAD Y SALUD EN EL TRABAJO'],
            ['SALUD OCUPACIONAL', 'TECNOLOGICO', 'SEGURIDAD Y SALUD EN EL TRABAJO'],
            ['DESARROLLO DE COLECCIONES PARA LA INDUSTRIA DE LA MODA', 'TECNOLOGICO', 'TECNOLOGIA EN PROCESOS INDUSTRIALES'],
            ['BILINGUAL EXPERT ON BUSINESS PROCESS OUTSOURCING', 'TECNICO', 'Tecnología en Gestión de Negocios Internacionales'],
            ['CONTABILIZACION DE OPERACIONES CONTABLE FINANCIERA', 'TECNICO', 'TECNOLOGIA EN GESTION CONTABLE'],
            ['Negociación Internacional', 'TECNOLOGICO', 'Administración de Negocios Internacionales'],
            ['PROCESOS PARA LA COMERCIALIZACION INTERNACIONAL', 'TECNOLOGICO', 'Administración de Negocios Internacionales'],
            ['PROGRAMACIÓN DE SOFTWARE', 'TECNICO', 'INGENIERÍA DE SISTEMAS ARTICULADO POR CICLOS CON TECNOLOGÍA EN DESARROLLO DE SOFTWARE'],
            ['PROGRAMACION PARA ANALITICA DE DATOS', 'TECNICO', 'INGENIERÍA EN CIENCIA DE DATOS ARTICULADO POR CICLOS CON TECNOLOGÍA EN GESTIÓN Y ANALÍTICA DE DATOS'],
            ['SISTEMAS TELEINFORMÁTICOS', 'TECNICO', 'INGENIERÍA DE SISTEMAS ARTICULADO POR CICLOS CON TECNOLOGÍA EN DESARROLLO DE SOFTWARE'],
            ['Tecnico operaciones de Comercio Exterior', 'TECNICO', 'Tecnología en Gestión de Negocios Internacionales'],
            ['GESTIÓN DE REDES DE DATOS', 'TECNOLOGICO', 'INGENIERÍA DE SISTEMAS ARTICULADO POR CICLOS CON TECNOLOGÍA EN DESARROLLO DE SOFTWARE'],
            ['DESARROLLO DE PROCESOS DE MERCADEO', 'TECNOLOGICO', 'PUBLICIDAD'],
            ['COMPRAS Y SUMINISTROS', 'TECNICO', 'Tecnología en Gestión Logística Internacional'],
            ['DESARROLLO DE OPERACIONES LOGÍSTICA EN LA CADENA DE ABASTECIMIENTO', 'TECNICO', 'Tecnología en Gestión Logística Internacional'],
            ['OPERACIONES COMERCIALES EN ALMACENES DE CADENA', 'TECNICO', 'Tecnología en Gestión Logística Internacional'],
            ['Tecnico en Comercio Internacional', 'TECNICO', 'Tecnología en Gestión de Negocios Internacionales'],
            ['Gestión Loigistica', 'TECNOLOGICO', 'Administración Logistica'],
            ['INTEGRACION DE OPERACIONES LOGISTICAS', 'TECNOLOGICO', 'Administración Logistica'],
            ['AUTOMATIZACIÓN DE SISTEMAS MECATRÓNICOS', 'TECNOLOGICO', 'INGENIERIA ELECTRÓNICA'],
            ['AUTOMATIZACIÓN INDUSTRIAL', 'TECNOLOGICO', 'INGENIERIA ELECTRÓNICA'],
            ['DISEÑO E INTEGRACIÓN DE AUTOMATISMOS MECATRÓNICOS', 'TECNOLOGICO', 'INGENIERIA ELECTRÓNICA'],
            ['GESTIÓN DE REDES DE DATOS', 'TECNOLOGICO', 'TECNOLOGIA EN DESARROLLO DE SISTEMAS ELECTRONICOS'],
            ['IMPLEMENTACIÓN Y MTTO DE SISTEMAS DE INSTRUMENTACIÓN Y CONTROL DE PROCESOS', 'TECNOLOGICO', 'INGENIERIA ELECTRÓNICA'],
            ['MTTO DE EQUIPOS BIOMEDICOS', 'TECNOLOGICO', 'INGENIERIA ELECTRÓNICA'],
            ['MTTO DE EQUIPOS DE AIRE ACONDICIONADO Y REFRIGERACIÓN', 'TECNICO', 'TECNOLOGIA EN DESARROLLO DE SISTEMAS ELECTRONICOS'],
            ['INSTALACIONES ELECTRICAS PARA VIVIENDAS', 'TECNICO', 'TECNOLOGIA EN DESARROLLO DE SISTEMAS ELECTRONICOS'],
            ['INSTRUMENTACIÓN INDUSTRIAL', 'TECNICO', 'TECNOLOGIA EN DESARROLLO DE SISTEMAS ELECTRONICOS'],
            ['MTTO ELECTROMECANICO INDUSTRIAL', 'TECNOLOGICO', 'INGENIERIA ELECTRÓNICA'],
            ['DESARROLLO DE PRODUCTOS ELECTRONICOS', 'TECNOLOGICO', 'INGENIERIA ELECTRÓNICA'],
            ['MTTO ELECTROMECANICO E INSTRUMENTACIÓN INDUSTRIAL', 'TECNOLOGICO', 'INGENIERIA ELECTRÓNICA'],
        ];
    }
};
