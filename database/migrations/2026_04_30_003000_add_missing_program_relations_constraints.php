<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('progrel')) {
            if (!$this->constraintExists('progrel', 'progrel_dir_prog_unique')) {
                DB::statement('ALTER TABLE progrel ADD UNIQUE KEY progrel_dir_prog_unique (dir, prog)');
            }
            if (!$this->constraintExists('progrel', 'progrel_dir_idx')) {
                DB::statement('ALTER TABLE progrel ADD INDEX progrel_dir_idx (dir)');
            }
            if (!$this->constraintExists('progrel', 'progrel_prog_idx')) {
                DB::statement('ALTER TABLE progrel ADD INDEX progrel_prog_idx (prog)');
            }
        }

        if (Schema::hasTable('programa_procedencia_relacion')) {
            if (!$this->constraintExists('programa_procedencia_relacion', 'programa_proc_ext_pca_unique')) {
                DB::statement('ALTER TABLE programa_procedencia_relacion ADD UNIQUE KEY programa_proc_ext_pca_unique (programa_ext_cod, programa_pca_cod)');
            }
            if (!$this->constraintExists('programa_procedencia_relacion', 'programa_proc_pca_idx')) {
                DB::statement('ALTER TABLE programa_procedencia_relacion ADD INDEX programa_proc_pca_idx (programa_pca_cod)');
            }
            if (!$this->constraintExists('programa_procedencia_relacion', 'programa_proc_ext_idx')) {
                DB::statement('ALTER TABLE programa_procedencia_relacion ADD INDEX programa_proc_ext_idx (programa_ext_cod)');
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('progrel')) {
            if ($this->constraintExists('progrel', 'progrel_dir_prog_unique')) {
                DB::statement('ALTER TABLE progrel DROP INDEX progrel_dir_prog_unique');
            }
            if ($this->constraintExists('progrel', 'progrel_dir_idx')) {
                DB::statement('ALTER TABLE progrel DROP INDEX progrel_dir_idx');
            }
            if ($this->constraintExists('progrel', 'progrel_prog_idx')) {
                DB::statement('ALTER TABLE progrel DROP INDEX progrel_prog_idx');
            }
        }

        if (Schema::hasTable('programa_procedencia_relacion')) {
            if ($this->constraintExists('programa_procedencia_relacion', 'programa_proc_ext_pca_unique')) {
                DB::statement('ALTER TABLE programa_procedencia_relacion DROP INDEX programa_proc_ext_pca_unique');
            }
            if ($this->constraintExists('programa_procedencia_relacion', 'programa_proc_pca_idx')) {
                DB::statement('ALTER TABLE programa_procedencia_relacion DROP INDEX programa_proc_pca_idx');
            }
            if ($this->constraintExists('programa_procedencia_relacion', 'programa_proc_ext_idx')) {
                DB::statement('ALTER TABLE programa_procedencia_relacion DROP INDEX programa_proc_ext_idx');
            }
        }
    }

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
