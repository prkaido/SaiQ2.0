<?php

namespace Tests\Feature;

use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomologacionAsignaturaCambioPensumTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function cambio_pensum_prefills_internal_equivalences_for_selected_program(): void
    {
        $usuario = UsuarioFactory::new()->create();
        [$asignaturaAnteriorId, $asignaturaNuevaId] = $this->seedCambioPensumData();

        DB::table('equiv')->insert([
            'asg_pca' => (string) $asignaturaNuevaId,
            'asg_ext' => (string) $asignaturaAnteriorId,
        ]);

        $response = $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->post('/homologaciones/asignatura/revision', [
                'nom' => 'Laura',
                'ape' => 'Rojas',
                'ide' => 'PENSUM-001',
                'per' => '2026-1',
                'pca' => '777',
                'pla' => 77702,
                'cambio_pensum' => '1',
                '_accion' => 'continuar',
            ]);

        $response->assertOk();
        $response->assertViewIs('homologaciones.asignatura.review');
        $response->assertSee('Asignatura Pensum Anterior');
        $response->assertSee('value="4.5"', false);
        $response->assertSee('<option value="2" selected>Reintegro</option>', false);
    }

    private function seedCambioPensumData(): array
    {
        DB::table('periodo')->insert([
            'id' => '2026-1',
            'nombre' => 'Primer Semestre 2026',
            'activo' => 1,
        ]);

        DB::table('institucion')->insert([
            'id' => 1,
            'nombre' => 'Corporacion Universitaria Politecnico Costa Atlantica',
            'abrev' => 'PC',
            'tipo' => 'IES',
        ]);

        DB::table('programa')->insert([
            'cod' => '777',
            'nombre' => 'Programa Cambio Pensum',
            'nivel' => 3,
            'enpca' => 1,
            'activo' => 1,
            'inst' => null,
        ]);

        DB::table('plan')->insert([
            ['id' => 77701, 'programa' => '777', 'num' => '77701'],
            ['id' => 77702, 'programa' => '777', 'num' => '77702'],
        ]);

        DB::table('semestre')->updateOrInsert(['id' => 1], ['romano' => 'I']);

        $asignaturaAnteriorId = DB::table('asignatura')->insertGetId([
            'cod' => '777001',
            'nombre' => 'Asignatura Pensum Anterior',
            'programa' => '777',
            'plan' => 77701,
            'nivel' => 1,
            'creditos' => '3',
            'activo' => 1,
        ]);

        $asignaturaNuevaId = DB::table('asignatura')->insertGetId([
            'cod' => '777101',
            'nombre' => 'Asignatura Pensum Nuevo',
            'programa' => '777',
            'plan' => 77702,
            'nivel' => 1,
            'creditos' => '3',
            'activo' => 1,
        ]);

        return [$asignaturaAnteriorId, $asignaturaNuevaId];
    }
}
