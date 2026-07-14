<?php

namespace Tests\Integration;

use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomologacionCompleteFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_complete_full_homologacion_programa_flow(): void
    {
        // 1. Setup
        $usuario = UsuarioFactory::new()->estudiante()->create(['id' => 'student001']);
        $this->seedHomologacionTestData();

        // 2. Login
        $loginResponse = $this->post('/login', [
            'us' => 'student001',
            'pa' => 'password123', // Assuming default factory password
        ]);
        $loginResponse->assertRedirect('/inicio');

        // 3. Access Dashboard
        $dashboardResponse = $this->get('/inicio');
        $dashboardResponse->assertOk();

        // 4. Access Form
        $formResponse = $this->get('/homologaciones/programa');
        $formResponse->assertOk();
        $formResponse->assertViewHas('programasPca');
        $formResponse->assertSee('Ingeniería Informática');

        // 5. Submit Homologacion
        $submitResponse = $this->post('/homologaciones/programa', [
            'nom' => 'Juan',
            'ape' => 'Perez',
            'ide' => '1234567890',
            'per' => '2026-1',
            'pca' => 'ING001',
            'pla' => 1,
            'ext' => 'EXT001',
            'tipo' => 'e',
            '_accion' => 'generar',
        ]);

        $submitResponse->assertOk();
        $submitResponse->assertViewIs('homologaciones.programa.resultado');
        $submitResponse->assertSee('INGENIERÍA INFORMÁTICA');

        // 6. Verify Data Was Saved
        $this->assertDatabaseHas('homologacion', [
            'estudiante_nom' => 'Juan',
            'estudiante_ape' => 'Perez',
            'user_id' => 'student001',
        ]);

        // 7. Verify Audit Trail
        $this->assertDatabaseHas('audit_log', [
            'action' => 'GENERAR_HOMOLOGACION_PROGRAMA',
            'user_id' => 'student001',
        ]);

        // 8. Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
    }

    /** @test */
    public function can_save_draft_and_view_later(): void
    {
        $usuario = UsuarioFactory::new()->create(['id' => 'user002']);
        $this->seedHomologacionTestData();

        // Save as draft
        $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->post('/homologaciones/programa', [
                'nom' => 'Maria',
                'ape' => 'Garcia',
                'ide' => '9876543210',
                'per' => '2026-1',
                'pca' => 'ING001',
                'pla' => 1,
                'ext' => 'EXT001',
                '_accion' => 'borrador',
            ])->assertRedirect('/homologaciones/borradores');

        // Verify draft exists
        $this->assertDatabaseHas('homologacion', [
            'estudiante_nom' => 'Maria',
            'estado' => 'borrador',
        ]);

        // View drafts
        $response = $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->get('/homologaciones/borradores');

        $response->assertOk();
    }

    /** @test */
    public function can_save_asignatura_draft_with_typed_origin_program(): void
    {
        $usuario = UsuarioFactory::new()->create(['id' => 'user004']);
        $this->seedHomologacionTestData();

        $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->post('/homologaciones/asignatura/revision', [
                'nom' => 'Laura',
                'ape' => 'Martinez',
                'ide' => 'ASG-001',
                'per' => '2026-1',
                'pca' => 'ING001',
                'pla' => 1,
                'ext' => 'Tecnologia en Desarrollo de Software',
                'ins' => DB::table('institucion')->value('id'),
                'pca_ciclo_universitario' => '0',
                'pla_ciclo_universitario' => 0,
                '_accion' => 'borrador',
            ])->assertRedirect('/homologaciones/borradores');

        $this->assertDatabaseHas('homologacion', [
            'estudiante_id' => 'ASG-001',
            'tipo' => 'asignatura',
            'programa_ext_nombre' => 'Tecnologia en Desarrollo de Software',
            'programa_ext_cod' => null,
        ]);

        $homologacionId = DB::table('homologacion')->where('estudiante_id', 'ASG-001')->value('id');

        $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->get(route('homologaciones.borradores.edit', $homologacionId))
            ->assertOk()
            ->assertSee('Tecnologia en Desarrollo de Software');

        $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->post('/homologaciones/asignatura/revision', [
                'homologacion_id' => $homologacionId,
                'nom' => 'Laura',
                'ape' => 'Martinez',
                'ide' => 'ASG-001',
                'per' => '2026-1',
                'pca' => 'ING001',
                'pla' => 1,
                'ext' => 'Tecnologia en Analisis de Software',
                'ins' => DB::table('institucion')->value('id'),
                '_accion' => 'borrador',
            ])->assertRedirect('/homologaciones/borradores');

        $this->assertSame(1, DB::table('homologacion')->count());
        $this->assertDatabaseHas('homologacion', [
            'id' => $homologacionId,
            'programa_ext_nombre' => 'Tecnologia en Analisis de Software',
        ]);
        $this->assertDatabaseHas('audit_log', [
            'homologacion_id' => $homologacionId,
            'action' => 'ACTUALIZAR_BORRADOR_ASIGNATURA',
        ]);
    }

    /** @test */
    public function regenerating_completed_asignatura_shows_correction_format(): void
    {
        $usuario = UsuarioFactory::new()->create(['id' => 'user_correction_asg']);
        $this->seedHomologacionTestData();

        $institucionId = DB::table('institucion')->value('id');
        $homologacionId = DB::table('homologacion')->insertGetId([
            'user_id' => $usuario->id,
            'tipo' => 'asignatura',
            'estudiante_nom' => 'Andrea',
            'estudiante_ape' => 'Lopez',
            'estudiante_id' => 'COR-ASG-001',
            'periodo' => '2026-1',
            'programa_pca_cod' => 'ING001',
            'plan_id' => 1,
            'programa_ext_cod' => null,
            'programa_ext_nombre' => 'Tecnologia en Desarrollo de Software',
            'institucion_id' => $institucionId,
            'tipo_estudio' => 'e',
            'estado' => 'completado',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $asignatura = DB::table('asignatura')->where('programa', 'ING001')->first();

        $response = $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->post('/homologaciones/asignatura/resultado', [
                'homologacion_id' => $homologacionId,
                'nom' => 'Andrea Corregida',
                'ape' => 'Lopez',
                'ide' => 'COR-ASG-001',
                'per' => '2026-1',
                'pex' => 'Tecnologia en Desarrollo de Software',
                'pca' => 'ING001',
                'pla' => 1,
                'ins' => $institucionId,
                'tip' => 0,
                'semestre' => '3',
                'obs' => 'Correccion de datos',
                't' . $asignatura->id => 'Matematicas Fundamentales',
                'n' . $asignatura->id => '4.0',
            ]);

        $response->assertOk();
        $response->assertSee('FORMATO 12 - CORRECCION');
        $response->assertSee('FORMATO DE CORRECCION');

        $this->assertDatabaseHas('audit_log', [
            'homologacion_id' => $homologacionId,
            'action' => 'ACTUALIZAR_HOMOLOGACION_ASIGNATURA',
        ]);
    }

    /** @test */
    public function can_complete_asignatura_flow_with_university_cycle(): void
    {
        $usuario = UsuarioFactory::new()->create(['id' => 'user005']);
        $this->seedHomologacionTestData();

        $review = $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->post('/homologaciones/asignatura/revision', [
                'nom' => 'Camila',
                'ape' => 'Rojas',
                'ide' => 'ASG-CICLO-001',
                'per' => '2026-1',
                'pca' => 'ING001',
                'pla' => 1,
                'ext' => 'Tecnologia en Desarrollo de Software',
                'ins' => DB::table('institucion')->value('id'),
                'homologar_ciclo_universitario' => '1',
                'pca_ciclo_universitario' => 'INGUNI',
                'pla_ciclo_universitario' => 2,
                '_accion' => 'continuar',
            ]);

        $review->assertOk();
        $review->assertSee('Equivalencias para Ciclo Universitario');

        $principal = DB::table('homologacion')
            ->where('estudiante_id', 'ASG-CICLO-001')
            ->where('programa_pca_cod', 'ING001')
            ->first();
        $ciclo = DB::table('homologacion')
            ->where('homologacion_origen_id', $principal->id)
            ->where('ciclo_universitario', 1)
            ->first();

        $this->assertNotNull($ciclo);
        $this->assertSame('Tecnologia en Desarrollo de Software', $ciclo->programa_ext_nombre);
        $this->assertSame($principal->institucion_id, $ciclo->institucion_id);

        $asignaturaPrincipal = DB::table('asignatura')->where('programa', 'ING001')->first();
        $asignaturaCiclo = DB::table('asignatura')->where('programa', 'INGUNI')->first();

        $result = $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->post('/homologaciones/asignatura/resultado', [
                'homologacion_id' => $principal->id,
                'homologacion_ciclo_universitario_id' => $ciclo->id,
                'nom' => 'Camila',
                'ape' => 'Rojas',
                'ide' => 'ASG-CICLO-001',
                'per' => '2026-1',
                'pex' => 'Tecnologia en Desarrollo de Software',
                'pca' => 'ING001',
                'pla' => 1,
                'ins' => $principal->institucion_id,
                'tip' => 0,
                'semestre' => '5',
                'obs' => 'Ciclo universitario incluido',
                'homologar_ciclo_universitario' => '1',
                'pca_ciclo_universitario' => 'INGUNI',
                'pla_ciclo_universitario' => 2,
                't' . $asignaturaPrincipal->id => 'Matematicas Fundamentales',
                'n' . $asignaturaPrincipal->id => '4.2',
                'ct' . $asignaturaCiclo->id => 'Matematicas Universitarias',
                'cn' . $asignaturaCiclo->id => '4.4',
            ]);

        $result->assertOk();
        $result->assertSee('CICLO UNIVERSITARIO');

        $this->assertDatabaseHas('homologacion', [
            'id' => $ciclo->id,
            'homologacion_origen_id' => $principal->id,
            'programa_pca_cod' => 'INGUNI',
            'estado' => 'completado',
            'ciclo_universitario' => 1,
        ]);
        $this->assertDatabaseHas('homologacion_detalle', [
            'homologacion_id' => $ciclo->id,
            'asignatura_ext_nombre' => 'Matematicas Universitarias',
            'nota' => '4.4',
            'tiene_equivalencia' => 1,
        ]);
    }

    /** @test */
    public function password_change_flow(): void
    {
        $usuario = UsuarioFactory::new()->create([
            'id' => 'user003',
            'clave' => bcrypt('old_password'),
        ]);

        // Change password
        $response = $this->withSession(['x' => $usuario->id, 'tus' => 3])
            ->post('/clave', [
                'ca' => 'old_password',
                'nc' => 'new_secure_password_123',
                'rc' => 'new_secure_password_123',
            ]);

        $response->assertRedirect();

        // Verify new password works
        $this->post('/login', [
            'us' => 'user003',
            'pa' => 'new_secure_password_123',
        ])->assertRedirect('/inicio');
    }

    /** @test */
    public function admin_can_create_new_usuario(): void
    {
        $admin = UsuarioFactory::new()->admin()->create(['id' => 'admin_001']);

        DB::table('programa')->insert([
            'cod' => 'ADM001',
            'nombre' => 'Programa Administrativo',
            'nivel' => 1,
            'enpca' => 1,
            'activo' => 1,
        ]);

        $response = $this->withSession(['x' => $admin->id, 'tus' => 1])
            ->post('/admin/usuarios', [
                'no' => 'new_user_001',
                'co' => 'password123',
                'pr' => 'ADM001',
            ]);

        $this->assertDatabaseHas('usuario', [
            'id' => 'new_user_001',
        ]);
    }

    /** @test */
    public function concurrent_homologaciones_are_independent(): void
    {
        $user1 = UsuarioFactory::new()->create(['id' => 'user_a']);
        $user2 = UsuarioFactory::new()->create(['id' => 'user_b']);

        $this->seedHomologacionTestData();

        // User 1 submits
        $this->withSession(['x' => $user1->id, 'tus' => 3])
            ->post('/homologaciones/programa', [
                'nom' => 'Usuario A',
                'ape' => 'Apellido A',
                'ide' => 'ID001',
                'per' => '2026-1',
                'pca' => 'ING001',
                'pla' => 1,
                'ext' => 'EXT001',
                '_accion' => 'borrador',
            ]);

        // User 2 submits
        $this->withSession(['x' => $user2->id, 'tus' => 3])
            ->post('/homologaciones/programa', [
                'nom' => 'Usuario B',
                'ape' => 'Apellido B',
                'ide' => 'ID002',
                'per' => '2026-1',
                'pca' => 'ING001',
                'pla' => 1,
                'ext' => 'EXT001',
                '_accion' => 'borrador',
            ]);

        // Verify both exist independently
        $count = DB::table('homologacion')->count();
        $this->assertGreaterThanOrEqual(2, $count);

        $this->assertDatabaseHas('homologacion', ['estudiante_nom' => 'Usuario A']);
        $this->assertDatabaseHas('homologacion', ['estudiante_nom' => 'Usuario B']);
    }

    private function seedHomologacionTestData(): void
    {
        DB::table('periodo')->insert([
            'id' => '2026-1',
            'nombre' => 'Primer Semestre 2026',
            'activo' => 1,
        ]);

        // Insertar institucion primero
        $institucionId = DB::table('institucion')->insertGetId([
            'nombre' => 'Universidad Externa',
            'abrev' => 'EX',
            'tipo' => 'IES',
        ]);

        // Insertar niveles
        $nivel1Id = DB::table('nivel')->insertGetId([
            'descripcion' => 'Primer Semestre',
            'romano' => 'I',
            'tipo' => 'Semestre',
        ]);

        // Insertar programa PCA
        DB::table('programa')->insert([
            'cod' => 'ING001',
            'nombre' => 'Ingeniería en Sistemas',
            'nivel' => 1,
            'enpca' => 1,
            'activo' => 1,
            'inst' => null, // PCA no tiene institucion externa
        ]);

        DB::table('programa')->insert([
            'cod' => 'INGUNI',
            'nombre' => 'Ingenieria de Sistemas Profesional',
            'nivel' => 3,
            'enpca' => 1,
            'activo' => 1,
            'inst' => null,
        ]);

        // Insertar programa externo
        DB::table('programa')->insert([
            'cod' => 'EXT001',
            'nombre' => 'Ingeniería Informática',
            'nivel' => 1,
            'enpca' => 0,
            'activo' => 1,
            'inst' => $institucionId,
        ]);

        DB::table('programa_procedencia_relacion')->insert([
            'programa_ext_cod' => 'EXT001',
            'programa_pca_cod' => 'ING001',
            'institucion_id' => $institucionId,
            'nivel_origen' => 'TECNICO',
            'activo' => 1,
        ]);

        // Insertar plan
        $planId = DB::table('plan')->insertGetId([
            'id' => 1,
            'programa' => 'ING001',
            'num' => '2020',
        ]);

        $planCicloId = DB::table('plan')->insertGetId([
            'id' => 2,
            'programa' => 'INGUNI',
            'num' => '2021',
        ]);

        // Insertar asignaturas PCA
        $asignaturaPcaId = DB::table('asignatura')->insertGetId([
            'cod' => 'ING001-001',
            'nombre' => 'Matemáticas Básicas',
            'programa' => 'ING001',
            'plan' => $planId,
            'nivel' => $nivel1Id,
            'creditos' => '3',
            'ihsemana' => 4,
        ]);

        DB::table('asignatura')->insert([
            'cod' => 'INGUNI-001',
            'nombre' => 'Matematicas Universitarias',
            'programa' => 'INGUNI',
            'plan' => $planCicloId,
            'nivel' => $nivel1Id,
            'creditos' => '3',
            'ihsemana' => 4,
        ]);

        // Insertar asignaturas externas
        $asignaturaExtId = DB::table('asignatura')->insertGetId([
            'cod' => 'EXT001-001',
            'nombre' => 'Matemáticas Fundamentales',
            'programa' => 'EXT001',
            'plan' => null,
            'nivel' => null,
            'creditos' => '3',
            'ihsemana' => 4,
        ]);

        // Insertar equivalencia
        DB::table('equiv')->insert([
            'asg_pca' => $asignaturaPcaId,
            'asg_ext' => 'EXT001-001',
        ]);

        DB::table('progrel')->insert([
            'dir' => 'ING001',
            'prog' => 'INGUNI',
            'activo' => 1,
        ]);
    }
}
