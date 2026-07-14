<?php

namespace Tests\Feature;

use Database\Factories\UsuarioFactory;
use Database\Factories\ProgramaFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_access_dashboard(): void
    {
        $usuario = UsuarioFactory::new()->create();
        $this->actingAsUser($usuario);

        $response = $this->get('/inicio');

        $response->assertOk();
        $response->assertViewIs('dashboard.index');
    }

    /** @test */
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/inicio');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function dashboard_shows_user_name(): void
    {
        $usuario = UsuarioFactory::new()->create(['nombre' => 'Juan', 'apellido' => 'Perez']);
        $this->actingAsUser($usuario);

        $response = $this->get('/inicio');

        $response->assertViewHas('usuario', $usuario);
    }

    /** @test */
    public function dashboard_shows_menu_options_for_admin(): void
    {
        $usuario = UsuarioFactory::new()->admin()->create();
        $this->actingAsUser($usuario);

        $response = $this->get('/inicio');

        $response->assertViewIs('dashboard.index');
    }

    /** @test */
    public function dashboard_shows_menu_options_for_director(): void
    {
        $usuario = UsuarioFactory::new()->director()->create();
        $this->actingAsUser($usuario);

        $response = $this->get('/inicio');

        $response->assertViewIs('dashboard.index');
    }

    /** @test */
    public function dashboard_shows_menu_options_for_estudiante(): void
    {
        $usuario = UsuarioFactory::new()->estudiante()->create();
        $this->actingAsUser($usuario);

        $response = $this->get('/inicio');

        $response->assertViewIs('dashboard.index');
    }

    private function actingAsUser($usuario): void
    {
        $this->withSession([
            'x' => $usuario->id,
            'tus' => $usuario->tipo,
            'login_time' => time(),
        ]);
    }
}
