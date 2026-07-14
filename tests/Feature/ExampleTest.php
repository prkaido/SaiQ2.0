<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_login_page_is_available()
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('HOMOLOGACIONES');
    }

    public function test_homologacion_programa_requires_session()
    {
        $response = $this->get('/homologaciones/programa');

        $response->assertRedirect('/login');
    }

    public function test_dashboard_requires_session()
    {
        $response = $this->get('/inicio');

        $response->assertRedirect('/login');
    }

    public function test_reconocimiento_titulo_requires_session()
    {
        $response = $this->get('/reconocimiento-titulo');

        $response->assertRedirect('/login');
    }

    public function test_password_change_requires_session()
    {
        $response = $this->get('/clave');

        $response->assertRedirect('/login');
    }

    public function test_admin_panel_requires_session()
    {
        $response = $this->get('/admin/instituciones');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_admin_routes()
    {
        $response = $this->withSession(['x' => 'sistemas', 'tus' => 2])
            ->get('/admin/instituciones');

        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_routes()
    {
        $response = $this->withSession(['x' => 'main', 'tus' => 1])
            ->get('/admin/instituciones');

        $response->assertOk();
    }

    public function test_dashboard_renders_academic_flow_menu()
    {
        $response = $this
            ->withSession(['x' => 'sistemas', 'tus' => 2])
            ->get('/inicio');

        $response->assertOk();
        $response->assertSee('Homologaci');
        $response->assertSee('Reconocimiento');
        $response->assertSee('Borradores');
    }

    public function test_dashboard_renders_admin_menu()
    {
        $response = $this
            ->withSession(['x' => 'main', 'tus' => 1])
            ->get('/inicio');

        $response->assertOk();
        $response->assertSee('Instituciones');
        $response->assertSee('Usuarios');
        $response->assertSee('Auditor');
    }

    public function test_borradores_requires_session()
    {
        $response = $this->get('/homologaciones/borradores');

        $response->assertRedirect('/login');
    }

    public function test_borradores_render_with_saiq_session()
    {
        $response = $this
            ->withSession(['x' => 'sistemas', 'tus' => 2])
            ->get('/homologaciones/borradores');

        $response->assertOk();
        $response->assertSee('Borradores');
        $response->assertSee('trazabilidad');
    }

    public function test_programa_draft_creates_traceability()
    {
        DB::beginTransaction();

        try {
            $baseData = $this->ensureHomologacionBaseData();

            $response = $this
                ->withSession(['x' => 'sistemas', 'tus' => 2])
                ->post('/homologaciones/programa', [
                    'nom' => 'Prueba',
                    'ape' => 'Borrador',
                    'ide' => 'TEST-DRAFT-001',
                    'per' => $baseData['periodo'],
                    'pca' => $baseData['programaPca'],
                    'pla' => $baseData['plan'],
                    'ext' => $baseData['programaExt'],
                    '_accion' => 'borrador',
                ]);

            $response->assertRedirect(route('homologaciones.borradores.index'));

            $homologacionId = DB::table('homologacion')
                ->where('estudiante_id', 'TEST-DRAFT-001')
                ->orderByDesc('id')
                ->value('id');

            $this->assertNotEmpty($homologacionId);
            $this->assertSame('borrador', DB::table('homologacion')->where('id', $homologacionId)->value('estado'));
            $this->assertTrue(DB::table('audit_log')
                ->where('homologacion_id', $homologacionId)
                ->where('action', 'GUARDAR_BORRADOR_PROGRAMA')
                ->exists());
        } finally {
            DB::rollBack();
        }
    }

    public function test_homologacion_programa_renders_with_saiq_session()
    {
        $response = $this
            ->withSession(['x' => 'sistemas', 'tus' => 2])
            ->get('/homologaciones/programa');

        $response->assertOk();
        $response->assertSee('Programa PCA');
    }

    public function test_reconocimiento_titulo_renders_with_saiq_session()
    {
        $response = $this
            ->withSession(['x' => 'sistemas', 'tus' => 2])
            ->get('/reconocimiento-titulo');

        $response->assertOk();
        $response->assertSee('Reconocimiento');
    }

    public function test_homologacion_asignatura_renders_with_saiq_session()
    {
        $response = $this
            ->withSession(['x' => 'sistemas', 'tus' => 2])
            ->get('/homologaciones/asignatura');

        $response->assertOk();
        $response->assertSee('Homologaci');
        $response->assertSee('placeholder="Escriba el programa de procedencia"', false);
        $response->assertDontSee('<select name="ext"', false);
    }

    public function test_admin_catalogs_render_with_saiq_session()
    {
        foreach ([
            '/admin/instituciones',
            '/admin/programas',
            '/admin/asignaturas',
            '/admin/equivalencias',
            '/admin/usuarios',
            '/admin/relaciones',
        ] as $uri) {
            $this->withSession(['x' => 'main', 'tus' => 1])
                ->get($uri)
                ->assertOk();
        }
    }

    public function test_legacy_programas_php_redirects_to_laravel_route()
    {
        $this->withSession(['x' => 'main', 'tus' => 1])
            ->get('/programa.php')
            ->assertRedirect('/admin/programas');

        $this->withSession(['x' => 'main', 'tus' => 1])
            ->get('/programas.php')
            ->assertRedirect('/admin/programas');
    }
}
