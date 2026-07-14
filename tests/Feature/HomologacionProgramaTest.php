<?php

namespace Tests\Feature;

use Database\Factories\UsuarioFactory;
use Database\Factories\ProgramaFactory;
use Database\Factories\AsignaturaFactory;
use Database\Factories\InstitucionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomologacionProgramaTest extends TestCase
{
    use RefreshDatabase;

    private UsuarioFactory $usuarioFactory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuarioFactory = UsuarioFactory::new();
    }

    /** @test */
    public function can_view_homologacion_programa_form(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $response = $this->get('/homologaciones/programa');

        $response->assertOk();
        $response->assertViewIs('homologaciones.programa.create');
    }

    /** @test */
    public function unauthenticated_user_cannot_access_homologacion(): void
    {
        $response = $this->get('/homologaciones/programa');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function form_contains_required_options(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $this->seedTestData();

        $response = $this->get('/homologaciones/programa');

        $response->assertViewHas('periodo');
        $response->assertViewHas('programasPca');
        $response->assertViewHas('programasExternos');
        $response->assertViewHas('planes');
    }

    /** @test */
    public function can_submit_homologacion_programa_and_save_as_draft(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $this->seedTestData();

        $response = $this->post('/homologaciones/programa', [
            'nom' => 'Juan',
            'ape' => 'Perez',
            'ide' => '1234567890',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            'tipo' => 'e',
            'semestre' => '3',
            'obs' => 'Observación de prueba',
            '_accion' => 'borrador',
        ]);

        $response->assertRedirect('/homologaciones/borradores');
        $response->assertSessionHas('status');
    }

    /** @test */
    public function can_edit_existing_programa_draft_without_creating_duplicate(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $this->seedTestData();

        $this->post('/homologaciones/programa', [
            'nom' => 'Juan',
            'ape' => 'Perez',
            'ide' => 'EDIT-001',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            '_accion' => 'borrador',
        ])->assertRedirect('/homologaciones/borradores');

        $homologacionId = DB::table('homologacion')->where('estudiante_id', 'EDIT-001')->value('id');

        $this->get(route('homologaciones.borradores.edit', $homologacionId))
            ->assertOk()
            ->assertSee('Editando homologaci');

        $this->post('/homologaciones/programa', [
            'homologacion_id' => $homologacionId,
            'nom' => 'Juan Carlos',
            'ape' => 'Perez',
            'ide' => 'EDIT-001',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            '_accion' => 'borrador',
        ])->assertRedirect('/homologaciones/borradores');

        $this->assertSame(1, DB::table('homologacion')->count());
        $this->assertDatabaseHas('homologacion', [
            'id' => $homologacionId,
            'estudiante_nom' => 'Juan Carlos',
            'estado' => 'borrador',
        ]);
        $this->assertDatabaseHas('audit_log', [
            'homologacion_id' => $homologacionId,
            'action' => 'ACTUALIZAR_BORRADOR_PROGRAMA',
        ]);
    }

    /** @test */
    public function can_edit_completed_programa_without_returning_it_to_draft(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $this->seedTestData();

        $this->post('/homologaciones/programa', [
            'nom' => 'Maria',
            'ape' => 'Garcia',
            'ide' => 'COMP-001',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            '_accion' => 'generar',
        ])->assertOk();

        $homologacionId = DB::table('homologacion')->where('estudiante_id', 'COMP-001')->value('id');

        $this->get(route('homologaciones.borradores.index', ['estado' => 'completado']))
            ->assertOk()
            ->assertSee('Editar completada');

        $this->get(route('homologaciones.borradores.edit', $homologacionId))
            ->assertOk()
            ->assertSee('Guardar cambios');

        $this->post('/homologaciones/programa', [
            'homologacion_id' => $homologacionId,
            'nom' => 'Maria Editada',
            'ape' => 'Garcia',
            'ide' => 'COMP-001',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            '_accion' => 'borrador',
        ])->assertRedirect('/homologaciones/borradores');

        $this->assertSame(1, DB::table('homologacion')->count());
        $this->assertDatabaseHas('homologacion', [
            'id' => $homologacionId,
            'estudiante_nom' => 'Maria Editada',
            'estado' => 'completado',
        ]);
        $this->assertDatabaseHas('audit_log', [
            'homologacion_id' => $homologacionId,
            'action' => 'ACTUALIZAR_HOMOLOGACION_PROGRAMA',
        ]);
    }

    /** @test */
    public function regenerating_completed_programa_shows_correction_format(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $this->seedTestData();

        $this->post('/homologaciones/programa', [
            'nom' => 'Maria',
            'ape' => 'Garcia',
            'ide' => 'COR-PROG-001',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            'tipo' => 'e',
            '_accion' => 'generar',
        ])->assertOk()
            ->assertDontSee('FORMATO DE CORRECCION');

        $homologacionId = DB::table('homologacion')->where('estudiante_id', 'COR-PROG-001')->value('id');

        $response = $this->post('/homologaciones/programa', [
            'homologacion_id' => $homologacionId,
            'nom' => 'Maria Corregida',
            'ape' => 'Garcia',
            'ide' => 'COR-PROG-001',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            'tipo' => 'e',
            '_accion' => 'generar',
        ]);

        $response->assertOk();
        $response->assertSee('FORMATO DE CORRECCION');
        $response->assertSee('CORRECCION - ESTUDIO DE ASIGNATURAS');

        $this->assertSame(1, DB::table('homologacion')->count());
        $this->assertDatabaseHas('audit_log', [
            'homologacion_id' => $homologacionId,
            'action' => 'ACTUALIZAR_HOMOLOGACION_PROGRAMA',
        ]);
    }

    /** @test */
    public function can_generate_homologacion_programa(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $this->seedTestData();

        $response = $this->post('/homologaciones/programa', [
            'nom' => 'Maria',
            'ape' => 'Garcia',
            'ide' => '9876543210',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            'tipo' => 'e',
            'pca_ciclo_universitario' => '0',
            'pla_ciclo_universitario' => 0,
            '_accion' => 'generar',
        ]);

        $response->assertOk();
        $response->assertViewIs('homologaciones.programa.resultado');
        $response->assertSee('id="saiq-pdf-content"', false);
        $response->assertSee("document.getElementById('saiq-pdf-content')", false);
        $response->assertSee('data-html2canvas-ignore="true"', false);
        $response->assertSee('ASIGNATURAS A HOMOLOGAR');
        $response->assertSee('class="sombra"', false);
    }

    /** @test */
    public function can_create_university_cycle_homologacion_with_shared_origin_data(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $this->seedTestData();

        $this->post('/homologaciones/programa', [
            'nom' => 'Laura',
            'ape' => 'Martinez',
            'ide' => 'CICLO-001',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            'tipo' => 'e',
            'homologar_ciclo_universitario' => '1',
            'pca_ciclo_universitario' => 'INGUNI',
            'pla_ciclo_universitario' => 2,
            '_accion' => 'borrador',
        ])->assertRedirect('/homologaciones/borradores');

        $principal = DB::table('homologacion')
            ->where('estudiante_id', 'CICLO-001')
            ->where('programa_pca_cod', 'ING001')
            ->first();

        $this->assertNotNull($principal);

        $ciclo = DB::table('homologacion')
            ->where('homologacion_origen_id', $principal->id)
            ->where('ciclo_universitario', 1)
            ->first();

        $this->assertNotNull($ciclo);
        $this->assertSame('INGUNI', $ciclo->programa_pca_cod);
        $this->assertSame('Laura', $ciclo->estudiante_nom);
        $this->assertSame('Martinez', $ciclo->estudiante_ape);
        $this->assertSame('EXT001', $ciclo->programa_ext_cod);
        $this->assertSame($principal->institucion_id, $ciclo->institucion_id);
        $this->assertSame('borrador', $ciclo->estado);
    }

    /** @test */
    public function generated_result_includes_university_cycle_document(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $this->seedTestData();

        $response = $this->post('/homologaciones/programa', [
            'nom' => 'Carlos',
            'ape' => 'Diaz',
            'ide' => 'CICLO-002',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            'tipo' => 'e',
            'homologar_ciclo_universitario' => '1',
            'pca_ciclo_universitario' => 'INGUNI',
            'pla_ciclo_universitario' => 2,
            '_accion' => 'generar',
        ]);

        $response->assertOk();
        $response->assertViewIs('homologaciones.programa.resultado');
        $response->assertSee('CICLO UNIVERSITARIO');

        $principal = DB::table('homologacion')
            ->where('estudiante_id', 'CICLO-002')
            ->where('programa_pca_cod', 'ING001')
            ->first();

        $this->assertDatabaseHas('homologacion', [
            'homologacion_origen_id' => $principal->id,
            'programa_pca_cod' => 'INGUNI',
            'estado' => 'completado',
            'ciclo_universitario' => 1,
        ]);
    }

    /** @test */
    public function homologacion_requires_student_name(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $response = $this->post('/homologaciones/programa', [
            'ape' => 'Perez',
            'ide' => '1234567890',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
        ]);

        $response->assertSessionHasErrors('nom');
    }

    /** @test */
    public function homologacion_requires_pca_program(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $response = $this->post('/homologaciones/programa', [
            'nom' => 'Juan',
            'ape' => 'Perez',
            'ide' => '1234567890',
            'per' => '2026-1',
            'pla' => 1,
            'ext' => 'EXT001',
        ]);

        $response->assertSessionHasErrors('pca');
    }

    /** @test */
    public function homologacion_rejects_invalid_pca_program(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $response = $this->post('/homologaciones/programa', [
            'nom' => 'Juan',
            'ape' => 'Perez',
            'ide' => '1234567890',
            'per' => '2026-1',
            'pca' => '0',
            'pla' => 1,
            'ext' => 'EXT001',
        ]);

        $response->assertSessionHasErrors('pca');
    }

    /** @test */
    public function audit_log_records_homologacion_creation(): void
    {
        $usuario = $this->usuarioFactory->create();
        $this->actingAsUser($usuario);

        $this->seedTestData();

        $this->post('/homologaciones/programa', [
            'nom' => 'Juan',
            'ape' => 'Perez',
            'ide' => '1234567890',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            '_accion' => 'borrador',
        ]);

        $this->assertDatabaseHas('audit_log', [
            'action' => 'GUARDAR_BORRADOR_PROGRAMA',
            'user_id' => $usuario->id,
        ]);
    }

    private function actingAsUser($usuario): void
    {
        $this->withSession([
            'x' => $usuario->id,
            'tus' => $usuario->tipo,
        ]);
    }

    private function seedTestData(): void
    {
        // Seed minimal test data for homologacion to work
        DB::table('periodo')->insert([
            'id' => '2026-1',
            'nombre' => 'Primer Semestre 2026',
            'activo' => 1,
        ]);

        DB::table('programa')->insert([
            ['cod' => 'ING001', 'nombre' => 'Ingenieria en Sistemas', 'nivel' => 1, 'enpca' => 1, 'activo' => 1, 'inst' => null],
            ['cod' => 'INGUNI', 'nombre' => 'Ingenieria de Sistemas Profesional', 'nivel' => 3, 'enpca' => 1, 'activo' => 1, 'inst' => null],
            ['cod' => 'EXT001', 'nombre' => 'Ingenieria Informatica', 'nivel' => 1, 'enpca' => 0, 'activo' => 1, 'inst' => '1'],
        ]);

        DB::table('plan')->insert([
            ['id' => 1, 'num' => '2020', 'programa' => 'ING001'],
            ['id' => 2, 'num' => '2021', 'programa' => 'INGUNI'],
        ]);

        DB::table('institucion')->insert([
            ['id' => 1, 'nombre' => 'Universidad Externa', 'abrev' => 'EX', 'activo' => 1],
        ]);

        DB::table('progrel')->insert([
            ['dir' => 'ING001', 'prog' => 'INGUNI', 'activo' => 1],
        ]);
    }
}
