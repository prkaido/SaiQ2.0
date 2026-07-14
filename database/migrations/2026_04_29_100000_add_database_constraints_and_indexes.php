<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega constraints de unicidad y relaciones faltantes para evitar duplicados
     * y asegurar integridad referencial
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // ===== TABLA: USUARIO =====
        // Agregar índice único en email (ya existe pero sin índice explícito)
        if (Schema::hasTable('usuario') && Schema::hasColumn('usuario', 'email')) {
            if (!$this->constraintExists('usuario', 'usuario_email_unique')) {
                DB::statement('ALTER TABLE usuario ADD UNIQUE KEY usuario_email_unique (email)');
            }
        }

        // ===== TABLA: INSTITUCION =====
        // Validar unicidad de nombre y abrev
        if (Schema::hasTable('institucion')) {
            if (!$this->constraintExists('institucion', 'institucion_nombre_unique')) {
                DB::statement('ALTER TABLE institucion ADD UNIQUE KEY institucion_nombre_unique (nombre)');
            }
            if (!$this->constraintExists('institucion', 'institucion_abrev_unique')) {
                DB::statement('ALTER TABLE institucion ADD UNIQUE KEY institucion_abrev_unique (abrev)');
            }
        }

        // ===== TABLA: PROGRAMA =====
        // Validar par (cod, inst) único
        if (Schema::hasTable('programa')) {
            if (!$this->constraintExists('programa', 'programa_cod_inst_unique')) {
                DB::statement('ALTER TABLE programa ADD UNIQUE KEY programa_cod_inst_unique (cod, inst)');
            }
            if (!$this->constraintExists('programa', 'programa_inst_idx')) {
                DB::statement('ALTER TABLE programa ADD INDEX programa_inst_idx (inst)');
            }
            if (!$this->constraintExists('programa', 'programa_nivel_idx')) {
                DB::statement('ALTER TABLE programa ADD INDEX programa_nivel_idx (nivel)');
            }
            if (!$this->constraintExists('programa', 'programa_activo_idx')) {
                DB::statement('ALTER TABLE programa ADD INDEX programa_activo_idx (activo)');
            }
        }

        // ===== TABLA: ASIGNATURA =====
        // Validar par (programa, codigo, plan) único
        if (Schema::hasTable('asignatura')) {
            if (!$this->constraintExists('asignatura', 'asig_prog_cod_plan_unique')) {
                DB::statement('ALTER TABLE asignatura ADD UNIQUE KEY asig_prog_cod_plan_unique (programa, codigo, plan)');
            }
            if (!$this->constraintExists('asignatura', 'asig_prog_plan_idx')) {
                DB::statement('ALTER TABLE asignatura ADD INDEX asig_prog_plan_idx (programa, plan)');
            }
            if (!$this->constraintExists('asignatura', 'asig_activo_idx')) {
                DB::statement('ALTER TABLE asignatura ADD INDEX asig_activo_idx (activo)');
            }
        }

        // ===== TABLA: EQUIV =====
        // Validar par (asg_pca, asg_ext) único
        if (Schema::hasTable('equiv')) {
            if (!$this->constraintExists('equiv', 'equiv_asg_pca_ext_unique')) {
                DB::statement('ALTER TABLE equiv ADD UNIQUE KEY equiv_asg_pca_ext_unique (asg_pca, asg_ext)');
            }
            if (!$this->constraintExists('equiv', 'equiv_asg_pca_idx')) {
                DB::statement('ALTER TABLE equiv ADD INDEX equiv_asg_pca_idx (asg_pca)');
            }
            if (!$this->constraintExists('equiv', 'equiv_asg_ext_idx')) {
                DB::statement('ALTER TABLE equiv ADD INDEX equiv_asg_ext_idx (asg_ext)');
            }
        }

        // ===== TABLA: PLAN =====
        // Validar par (programa, num) único
        if (Schema::hasTable('plan')) {
            if (!$this->constraintExists('plan', 'plan_prog_num_unique')) {
                DB::statement('ALTER TABLE plan ADD UNIQUE KEY plan_prog_num_unique (programa, num)');
            }
        }

        // ===== TABLA: HOMOLOGACION_DETALLE =====
        // Validar par (homologacion_id, asignatura_pca_cod) único
        if (Schema::hasTable('homologacion_detalle')) {
            if (!$this->constraintExists('homologacion_detalle', 'hom_det_hom_asg_unique')) {
                DB::statement('ALTER TABLE homologacion_detalle ADD UNIQUE KEY hom_det_hom_asg_unique (homologacion_id, asignatura_pca_cod)');
            }
            if (!$this->constraintExists('homologacion_detalle', 'hom_det_homolog_idx')) {
                DB::statement('ALTER TABLE homologacion_detalle ADD INDEX hom_det_homolog_idx (homologacion_id)');
            }
            if (!$this->constraintExists('homologacion_detalle', 'hom_det_asg_idx')) {
                DB::statement('ALTER TABLE homologacion_detalle ADD INDEX hom_det_asg_idx (asignatura_pca_cod)');
            }
        }

        // ===== TABLA: HOMOLOGACION =====
        // Agregar índices para búsquedas frecuentes
        if (Schema::hasTable('homologacion')) {
            if (!$this->constraintExists('homologacion', 'hom_user_idx')) {
                DB::statement('ALTER TABLE homologacion ADD INDEX hom_user_idx (user_id)');
            }
            if (!$this->constraintExists('homologacion', 'hom_periodo_idx')) {
                DB::statement('ALTER TABLE homologacion ADD INDEX hom_periodo_idx (periodo)');
            }
            if (!$this->constraintExists('homologacion', 'hom_estado_idx')) {
                DB::statement('ALTER TABLE homologacion ADD INDEX hom_estado_idx (estado)');
            }
            if (!$this->constraintExists('homologacion', 'hom_created_idx')) {
                DB::statement('ALTER TABLE homologacion ADD INDEX hom_created_idx (created_at)');
            }
            if (!$this->constraintExists('homologacion', 'hom_prog_pca_idx')) {
                DB::statement('ALTER TABLE homologacion ADD INDEX hom_prog_pca_idx (programa_pca_cod)');
            }
        }

        // ===== TABLA: USUARIO =====
        // Agregar índices para búsquedas frecuentes
        if (Schema::hasTable('usuario')) {
            if (!$this->constraintExists('usuario', 'usuario_tipo_idx')) {
                DB::statement('ALTER TABLE usuario ADD INDEX usuario_tipo_idx (tipo)');
            }
            if (!$this->constraintExists('usuario', 'usuario_activo_idx')) {
                DB::statement('ALTER TABLE usuario ADD INDEX usuario_activo_idx (activo)');
            }
            if (!$this->constraintExists('usuario', 'usuario_programa_idx')) {
                DB::statement('ALTER TABLE usuario ADD INDEX usuario_programa_idx (programa)');
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Las constraints se pueden revertir manualmente si es necesario
        // No se recomienda automatizarlo para evitar pérdida de datos
        
        $constraints = [
            'usuario' => ['usuario_email_unique', 'usuario_tipo_idx', 'usuario_activo_idx', 'usuario_programa_idx'],
            'institucion' => ['institucion_nombre_unique', 'institucion_abrev_unique'],
            'programa' => ['programa_cod_inst_unique', 'programa_inst_idx', 'programa_nivel_idx', 'programa_activo_idx'],
            'asignatura' => ['asig_prog_cod_plan_unique', 'asig_prog_plan_idx', 'asig_activo_idx'],
            'equiv' => ['equiv_asg_pca_ext_unique', 'equiv_asg_pca_idx', 'equiv_asg_ext_idx'],
            'plan' => ['plan_prog_num_unique'],
            'homologacion_detalle' => ['hom_det_hom_asg_unique', 'hom_det_homolog_idx', 'hom_det_asg_idx'],
            'homologacion' => ['hom_user_idx', 'hom_periodo_idx', 'hom_estado_idx', 'hom_created_idx', 'hom_prog_pca_idx'],
        ];

        foreach ($constraints as $table => $keys) {
            if (Schema::hasTable($table)) {
                foreach ($keys as $key) {
                    if ($this->constraintExists($table, $key)) {
                        DB::statement("ALTER TABLE $table DROP INDEX $key");
                    }
                }
            }
        }
    }

    /**
     * Verifica si un constraint existe en una tabla
     */
    private function constraintExists(string $table, string $constraintName): bool
    {
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
             WHERE TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND TABLE_SCHEMA = DATABASE()",
            [$table, $constraintName]
        );

        if (count($constraints) > 0) {
            return true;
        }

        $indexes = DB::select(
            "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_NAME = ? AND INDEX_NAME = ? AND TABLE_SCHEMA = DATABASE()",
            [$table, $constraintName]
        );

        return count($indexes) > 0;
    }
};
