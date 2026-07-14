<?php

namespace Tests\Feature;

use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSee('HOMOLOGACIONES');
    }

    /** @test */
    public function cannot_access_dashboard_without_login(): void
    {
        $response = $this->get('/inicio');
        $response->assertRedirect('/login');
    }

    /** @test */
    public function can_login_with_valid_credentials(): void
    {
        $usuario = UsuarioFactory::new()->create([
            'id' => 'admin001',
            'clave' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'us' => 'admin001',
            'pa' => 'password123',
        ]);

        $response->assertRedirect('/inicio');
        $this->assertSaiqAuthenticatedAs($usuario, 'saiq');
    }

    /** @test */
    public function cannot_login_with_invalid_credentials(): void
    {
        UsuarioFactory::new()->create([
            'id' => 'user001',
            'clave' => bcrypt('correct_password'),
        ]);

        $response = $this->post('/login', [
            'us' => 'user001',
            'pa' => 'wrong_password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('auth');
    }

    /** @test */
    public function cannot_login_with_nonexistent_user(): void
    {
        $response = $this->post('/login', [
            'us' => 'nonexistent',
            'pa' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('auth');
    }

    /** @test */
    public function cannot_login_with_inactive_user(): void
    {
        UsuarioFactory::new()->inactive()->create([
            'id' => 'inactive_user',
            'clave' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'us' => 'inactive_user',
            'pa' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('auth');
    }

    /** @test */
    public function session_data_is_stored_on_login(): void
    {
        $usuario = UsuarioFactory::new()->admin()->create([
            'id' => 'admin_user',
            'clave' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'us' => 'admin_user',
            'pa' => 'password123',
        ]);

        $this->assertNotNull(session('x'));
        $this->assertEquals('admin_user', session('x'));
        $this->assertEquals(1, session('tus')); // tipo = admin
    }

    /** @test */
    public function audit_log_is_created_on_login(): void
    {
        UsuarioFactory::new()->create([
            'id' => 'user001',
            'clave' => bcrypt('password123'),
        ]);

        $this->post('/login', [
            'us' => 'user001',
            'pa' => 'password123',
        ]);

        $this->assertDatabaseHas('audit_log', [
            'action' => 'LOGIN',
            'user_id' => 'user001',
        ]);
    }

    /** @test */
    public function can_logout(): void
    {
        $usuario = UsuarioFactory::new()->create();
        $this->actingAs($usuario);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertNull(session('x'));
    }

    /** @test */
    public function legacy_md5_password_is_automatically_rehashed(): void
    {
        $password = 'legacyPassword123';
        $md5Hash = md5($password);

        UsuarioFactory::new()->create([
            'id' => 'legacy_user',
            'clave' => $md5Hash,
        ]);

        $response = $this->post('/login', [
            'us' => 'legacy_user',
            'pa' => $password,
        ]);

        $response->assertRedirect('/inicio');

        // Verify password was rehashed
        $storedPassword = DB::table('usuario')
            ->where('id', 'legacy_user')
            ->value('clave');

        $this->assertNotEquals($md5Hash, $storedPassword);
        $this->assertTrue(password_verify($password, $storedPassword));
    }

    /** @test */
    public function login_requires_username(): void
    {
        $response = $this->post('/login', [
            'pa' => 'password123',
        ]);

        $response->assertSessionHasErrors('us');
    }

    /** @test */
    public function login_requires_password(): void
    {
        $response = $this->post('/login', [
            'us' => 'user001',
        ]);

        $response->assertSessionHasErrors('pa');
    }

    private function assertSaiqAuthenticatedAs($usuario, $guard = null): void
    {
        $this->assertTrue(session()->has('x'));
        $this->assertEquals($usuario->id, session('x'));
    }
}
