<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('usuario')) {
            Schema::create('usuario', function (Blueprint $table) {
                $table->string('id', 50)->primary();
                $table->string('clave', 255);
                $table->integer('tipo')->default(2);
                $table->string('programa', 10)->nullable();
                $table->string('firma', 255)->nullable();
                $table->integer('activo')->default(1);
                $table->string('nombre', 100)->nullable();
                $table->string('apellido', 100)->nullable();
                $table->string('email', 150)->nullable()->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('institucion')) {
            Schema::create('institucion', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 200);
                $table->string('abrev', 10)->nullable();
                $table->string('tipo', 20)->nullable();
                $table->string('ciudad', 80)->nullable();
                $table->string('pais', 80)->nullable();
                $table->integer('activo')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('nivel')) {
            Schema::create('nivel', function (Blueprint $table) {
                $table->increments('id');
                $table->string('descripcion', 50);
                $table->string('romano', 10)->nullable();
                $table->string('tipo', 20)->nullable();
                $table->integer('activo')->default(1);
            });
        }

        if (!Schema::hasTable('semestre')) {
            Schema::create('semestre', function (Blueprint $table) {
                $table->integer('id')->primary();
                $table->string('romano', 10);
            });
        }

        if (!Schema::hasTable('programa')) {
            Schema::create('programa', function (Blueprint $table) {
                $table->string('cod', 10)->primary();
                $table->string('nombre', 200);
                $table->integer('nivel')->default(1);
                $table->string('inst', 10)->nullable();
                $table->integer('enpca')->default(0);
                $table->integer('activo')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('plan')) {
            Schema::create('plan', function (Blueprint $table) {
                $table->increments('id');
                $table->string('programa', 10);
                $table->string('num', 20);
                $table->integer('vigencia')->nullable();
            });
        }

        if (!Schema::hasTable('asignatura')) {
            Schema::create('asignatura', function (Blueprint $table) {
                $table->increments('id');
                $table->string('cod', 20);
                $table->string('codigo', 20)->nullable();
                $table->string('nombre', 250)->nullable();
                $table->string('programa', 10)->nullable();
                $table->integer('plan')->nullable();
                $table->integer('nivel')->nullable();
                $table->string('creditos', 10)->nullable();
                $table->integer('ihsemana')->nullable();
                $table->integer('activo')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('equiv')) {
            Schema::create('equiv', function (Blueprint $table) {
                $table->increments('id');
                $table->string('asg_pca', 20);
                $table->string('asg_ext', 20);
            });
        }

        if (!Schema::hasTable('periodo')) {
            Schema::create('periodo', function (Blueprint $table) {
                $table->string('id', 20)->primary();
                $table->string('nombre', 100)->nullable();
                $table->integer('activo')->default(1);
            });
        }

        if (!Schema::hasTable('homologacion')) {
            Schema::create('homologacion', function (Blueprint $table) {
                $table->increments('id');
                $table->string('user_id', 50);
                $table->string('tipo', 20)->default('programa');
                $table->string('estudiante_nom', 100);
                $table->string('estudiante_ape', 100);
                $table->string('estudiante_id', 20);
                $table->string('periodo', 20);
                $table->string('programa_pca_cod', 10);
                $table->integer('plan_id');
                $table->string('programa_ext_cod', 10)->nullable();
                $table->integer('institucion_id')->nullable();
                $table->string('tipo_estudio', 1)->default('e');
                $table->string('semestre', 10)->nullable();
                $table->text('observaciones')->nullable();
                $table->string('estado', 20)->default('borrador');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('homologacion_detalle')) {
            Schema::create('homologacion_detalle', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('homologacion_id');
                $table->string('asignatura_pca_cod', 20);
                $table->string('asignatura_pca_nombre', 250)->nullable();
                $table->string('asignatura_ext_nombre', 250)->nullable();
                $table->string('semestre', 10)->nullable();
                $table->string('creditos', 10)->nullable();
                $table->string('nota', 10)->nullable();
                $table->integer('tiene_equivalencia')->default(0);
                $table->integer('orden')->default(0);
            });
        }

        if (!Schema::hasTable('progrel')) {
            Schema::create('progrel', function (Blueprint $table) {
                $table->increments('id');
                $table->string('dir', 10);
                $table->string('prog', 10);
                $table->integer('activo')->default(1);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'progrel',
            'homologacion_detalle',
            'homologacion',
            'periodo',
            'equiv',
            'asignatura',
            'plan',
            'programa',
            'semestre',
            'nivel',
            'institucion',
            'usuario',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
