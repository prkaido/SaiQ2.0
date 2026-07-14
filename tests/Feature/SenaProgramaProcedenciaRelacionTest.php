<?php

namespace Tests\Feature;

use App\Support\AcademicText;
use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SenaProgramaProcedenciaRelacionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sena_programs_are_related_to_institutional_programs(): void
    {
        $talentoHumano = DB::table('programa')
            ->where('enpca', 0)
            ->get()
            ->first(function ($programa) {
                $nombre = AcademicText::upper($programa->nombre);

                return str_contains($nombre, 'GEST')
                    && str_contains($nombre, 'TALENTO HUMANO');
            });

        $this->assertNotNull($talentoHumano);
        $this->assertDatabaseHas('programa_procedencia_relacion', [
            'programa_ext_cod' => $talentoHumano->cod,
            'programa_pca_cod' => '604',
            'activo' => 1,
        ]);
        $this->assertDatabaseHas('programa_procedencia_relacion', [
            'programa_ext_cod' => $talentoHumano->cod,
            'programa_pca_cod' => '614',
            'activo' => 1,
        ]);
    }

    /** @test */
    public function programa_form_receives_origin_program_relations(): void
    {
        $usuario = UsuarioFactory::new()->create();

        $response = $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->get('/homologaciones/programa');

        $response->assertOk();
        $response->assertViewHas('relacionesProcedencia');
        $response->assertSee('relacionesProcedencia', false);
        $response->assertSee('<optgroup label="ADMINISTRACION DEL TALENTO HUMANO">', false);
    }

    /** @test */
    public function reconocimiento_titulo_excludes_origin_programs(): void
    {
        $usuario = UsuarioFactory::new()->create();
        $programaProcedenciaCod = DB::table('programa_procedencia_relacion')
            ->value('programa_ext_cod');

        $this->assertNotNull($programaProcedenciaCod);

        $response = $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->get('/reconocimiento-titulo');

        $response->assertOk();
        $response->assertViewHas('programasReconocimiento', function ($programas) use ($programaProcedenciaCod) {
            return !$programas->pluck('cod')->contains($programaProcedenciaCod);
        });
    }

    /** @test */
    public function reconocimiento_titulo_includes_non_sena_legacy_programs(): void
    {
        $usuario = UsuarioFactory::new()->create();
        $institucionId = DB::table('institucion')->insertGetId([
            'nombre' => 'Instituto de Reconocimiento',
            'abrev' => 'IR',
            'tipo' => 'IES',
        ]);

        DB::table('programa')->insert([
            'cod' => 'IR001',
            'nombre' => 'Tecnologia en Gestion Reconocible',
            'nivel' => 2,
            'inst' => $institucionId,
            'enpca' => 0,
            'activo' => 0,
        ]);

        $response = $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->get('/reconocimiento-titulo');

        $response->assertOk();
        $response->assertViewHas('programasReconocimiento', function ($programas) {
            return $programas->pluck('cod')->contains('IR001');
        });
    }
}
