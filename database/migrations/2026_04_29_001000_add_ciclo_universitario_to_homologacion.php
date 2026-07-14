<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('homologacion')) {
            return;
        }

        Schema::table('homologacion', function (Blueprint $table) {
            if (!Schema::hasColumn('homologacion', 'ciclo_universitario')) {
                $column = $table->boolean('ciclo_universitario')->default(false);

                if (Schema::hasColumn('homologacion', 'tipo_estudio')) {
                    $column->after('tipo_estudio');
                }
            }

            if (!Schema::hasColumn('homologacion', 'homologacion_origen_id')) {
                $column = $table->unsignedInteger('homologacion_origen_id')->nullable()->index();

                if (Schema::hasColumn('homologacion', 'estado')) {
                    $column->after('estado');
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('homologacion')) {
            return;
        }

        Schema::table('homologacion', function (Blueprint $table) {
            if (Schema::hasColumn('homologacion', 'homologacion_origen_id')) {
                $table->dropColumn('homologacion_origen_id');
            }

            if (Schema::hasColumn('homologacion', 'ciclo_universitario')) {
                $table->dropColumn('ciclo_universitario');
            }
        });
    }
};
