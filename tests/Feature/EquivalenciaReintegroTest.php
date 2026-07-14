<?php

namespace Tests\Feature;

use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EquivalenciaReintegroTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function equivalencias_form_has_reintegro_section(): void
    {
        $usuario = UsuarioFactory::new()->admin()->create();
        $this->seedPensumData();

        $this->withSession(['x' => $usuario->id, 'tus' => 1])
            ->get('/admin/equivalencias')
            ->assertOk()
            ->assertSee('Nueva equivalencia por reintegro')
            ->assertSee('Asignatura pensum nuevo');
    }

    /** @test */
    public function admin_can_create_equivalencia_between_old_and_new_pensum(): void
    {
        $usuario = UsuarioFactory::new()->admin()->create();
        [$asignaturaAnteriorId, $asignaturaNuevaId] = $this->seedPensumData();

        $response = $this->withSession(['x' => $usuario->id, 'tus' => 1])
            ->post('/admin/equivalencias', [
                'tipo' => 'reintegro',
                'mp' => $asignaturaNuevaId,
                'me' => $asignaturaAnteriorId,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('equiv', [
            'asg_pca' => (string) $asignaturaNuevaId,
            'asg_ext' => (string) $asignaturaAnteriorId,
        ]);
    }

    /** @test */
    public function reintegro_equivalencia_requires_different_plans_from_same_program(): void
    {
        $usuario = UsuarioFactory::new()->admin()->create();
        [$asignaturaAnteriorId] = $this->seedPensumData();

        $response = $this->withSession(['x' => $usuario->id, 'tus' => 1])
            ->from('/admin/equivalencias')
            ->post('/admin/equivalencias', [
                'tipo' => 'reintegro',
                'mp' => $asignaturaAnteriorId,
                'me' => $asignaturaAnteriorId,
            ]);

        $response->assertRedirect('/admin/equivalencias');
        $response->assertSessionHasErrors('me');
    }

    private function seedPensumData(): array
    {
        DB::table('programa')->insert([
            'cod' => '777',
            'nombre' => 'Programa Reintegro',
            'nivel' => 3,
            'enpca' => 1,
            'activo' => 1,
            'inst' => null,
        ]);

        DB::table('plan')->insert([
            ['id' => 77701, 'programa' => '777', 'num' => '77701'],
            ['id' => 77702, 'programa' => '777', 'num' => '77702'],
        ]);

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
