<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        if (Schema::hasTable('homologacion')) {
            if ($this->columnExists('homologacion', 'institucion_id') && $this->columnExists('institucion', 'id')) {
                $this->ensureUnsignedColumn('homologacion', 'institucion_id');
            }

            if ($this->columnExists('homologacion_detalle', 'homologacion_id') && $this->columnExists('homologacion', 'id')) {
                $this->ensureUnsignedColumn('homologacion_detalle', 'homologacion_id');
            }
        }

        if (Schema::hasTable('asignatura')) {
            if ($this->columnExists('asignatura', 'plan') && $this->columnExists('plan', 'id')) {
                $this->ensureUnsignedColumn('asignatura', 'plan');
            }
        }

        if (Schema::hasTable('homologacion')) {
            if ($this->columnExists('homologacion', 'user_id') && $this->columnExists('usuario', 'id')) {
                $this->addForeignKey('homologacion', 'homologacion_user_id_fk', 'user_id', 'usuario', 'id', 'RESTRICT', 'NO ACTION');
            }

            if ($this->columnExists('homologacion', 'programa_pca_cod') && $this->columnExists('programa', 'cod')) {
                $this->addForeignKey('homologacion', 'homologacion_prog_pca_fk', 'programa_pca_cod', 'programa', 'cod', 'RESTRICT', 'NO ACTION');
            }

            if ($this->columnExists('homologacion', 'programa_ext_cod') && $this->columnExists('programa', 'cod')) {
                $this->addForeignKey('homologacion', 'homologacion_prog_ext_fk', 'programa_ext_cod', 'programa', 'cod', 'RESTRICT', 'NO ACTION');
            }

            if ($this->columnExists('homologacion', 'institucion_id') && $this->columnExists('institucion', 'id')) {
                $this->addForeignKey('homologacion', 'homologacion_institucion_fk', 'institucion_id', 'institucion', 'id', 'SET NULL', 'NO ACTION');
            }
        }

        if (Schema::hasTable('asignatura')) {
            if ($this->columnExists('asignatura', 'programa') && $this->columnExists('programa', 'cod')) {
                $this->addForeignKey('asignatura', 'asignatura_programa_fk', 'programa', 'programa', 'cod', 'RESTRICT', 'NO ACTION');
            }

            if ($this->columnExists('asignatura', 'plan') && $this->columnExists('plan', 'id')) {
                $this->addForeignKey('asignatura', 'asignatura_plan_fk', 'plan', 'plan', 'id', 'SET NULL', 'NO ACTION');
            }
        }

        if (Schema::hasTable('plan')) {
            if ($this->columnExists('plan', 'programa') && $this->columnExists('programa', 'cod')) {
                $this->addForeignKey('plan', 'plan_programa_fk', 'programa', 'programa', 'cod', 'RESTRICT', 'NO ACTION');
            }
        }

        if (Schema::hasTable('homologacion_detalle')) {
            if ($this->columnExists('homologacion_detalle', 'homologacion_id') && $this->columnExists('homologacion', 'id')) {
                $this->addForeignKey('homologacion_detalle', 'homologacion_detalle_homologacion_fk', 'homologacion_id', 'homologacion', 'id', 'CASCADE', 'NO ACTION');
            }
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->dropForeignKey('homologacion', 'homologacion_user_id_fk');
        $this->dropForeignKey('homologacion', 'homologacion_prog_pca_fk');
        $this->dropForeignKey('homologacion', 'homologacion_prog_ext_fk');
        $this->dropForeignKey('homologacion', 'homologacion_institucion_fk');
        $this->dropForeignKey('asignatura', 'asignatura_programa_fk');
        $this->dropForeignKey('asignatura', 'asignatura_plan_fk');
        $this->dropForeignKey('plan', 'plan_programa_fk');
        $this->dropForeignKey('homologacion_detalle', 'homologacion_detalle_homologacion_fk');
    }

    private function addForeignKey(string $table, string $constraint, string $column, string $referencesTable, string $referencesColumn, string $onDelete, string $onUpdate): void
    {
        if (!$this->constraintExists($table, $constraint)) {
            DB::statement(sprintf(
                'ALTER TABLE %s ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s) ON DELETE %s ON UPDATE %s',
                $table,
                $constraint,
                $column,
                $referencesTable,
                $referencesColumn,
                $onDelete,
                $onUpdate
            ));
        }
    }

    private function dropForeignKey(string $table, string $constraint): void
    {
        if (Schema::hasTable($table) && $this->constraintExists($table, $constraint)) {
            DB::statement(sprintf('ALTER TABLE %s DROP FOREIGN KEY %s', $table, $constraint));
        }
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        $result = DB::select(
            'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_SCHEMA = DATABASE()',
            [$table, $constraint]
        );

        return count($result) > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function ensureUnsignedColumn(string $table, string $column): void
    {
        if (!$this->columnExists($table, $column)) {
            return;
        }

        $columnInfo = DB::select('SHOW COLUMNS FROM ' . $table . ' WHERE Field = ?', [$column]);
        if (!$columnInfo) {
            return;
        }

        $type = $columnInfo[0]->Type;
        if (str_contains(strtolower($type), 'unsigned')) {
            return;
        }

        $nullable = strtoupper($columnInfo[0]->Null) === 'YES';
        $nullSql = $nullable ? ' NULL' : ' NOT NULL';
        DB::statement(sprintf('ALTER TABLE %s MODIFY COLUMN %s INT UNSIGNED%s', $table, $column, $nullSql));
    }
};
