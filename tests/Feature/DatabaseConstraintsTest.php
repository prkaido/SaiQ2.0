<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseConstraintsTest extends TestCase
{
    use RefreshDatabase;

    public function test_progrel_dir_prog_is_unique(): void
    {
        DB::table('progrel')->insert([
            'dir' => 'P001',
            'prog' => 'P002',
            'activo' => 1,
        ]);

        $this->expectException(QueryException::class);

        DB::table('progrel')->insert([
            'dir' => 'P001',
            'prog' => 'P002',
            'activo' => 1,
        ]);
    }

    public function test_programa_procedencia_relacion_ext_pca_is_unique(): void
    {
        if (!Schema::hasTable('programa_procedencia_relacion')) {
            $this->markTestSkipped('La tabla programa_procedencia_relacion no existe en este esquema.');
        }

        DB::table('programa_procedencia_relacion')->insert([
            'programa_ext_cod' => 'EXT001',
            'programa_pca_cod' => 'PCA001',
            'institucion_id' => null,
        ]);

        $this->expectException(QueryException::class);

        DB::table('programa_procedencia_relacion')->insert([
            'programa_ext_cod' => 'EXT001',
            'programa_pca_cod' => 'PCA001',
            'institucion_id' => null,
        ]);
    }
}
