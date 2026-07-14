<?php

namespace Tests;

use Database\Factories\UsuarioFactory;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Create a test usuario and act as them.
     *
     * @param array $attributes
     * @return \Database\Factories\UsuarioFactory
     */
    protected function actingAsUsuario(array $attributes = [])
    {
        $usuario = UsuarioFactory::new()->create($attributes);
        $this->withSession([
            'x' => $usuario->id,
            'tus' => $usuario->tipo ?? 3,
            'login_time' => time(),
            'ip' => $this->app['request']->ip(),
            'user_agent' => $this->app['request']->userAgent(),
        ]);

        return $usuario;
    }

    /**
     * Create a test admin usuario.
     *
     * @param array $attributes
     * @return \App\Models\Usuario
     */
    protected function createAdmin(array $attributes = [])
    {
        return $this->actingAsUsuario(array_merge(['tipo' => 1], $attributes));
    }

    /**
     * Create a test director usuario.
     *
     * @param array $attributes
     * @return \App\Models\Usuario
     */
    protected function createDirector(array $attributes = [])
    {
        return $this->actingAsUsuario(array_merge(['tipo' => 2], $attributes));
    }

    /**
     * Create a test student usuario.
     *
     * @param array $attributes
     * @return \App\Models\Usuario
     */
    protected function createStudent(array $attributes = [])
    {
        return $this->actingAsUsuario(array_merge(['tipo' => 3], $attributes));
    }

    /**
     * Assert user is authenticated in SaiQ session.
     */
    protected function assertSaiqAuthenticated(): void
    {
        $this->assertTrue(
            session()->has('x'),
            'User is not authenticated in SaiQ session.'
        );
    }

    /**
     * Assert user is not authenticated in SaiQ session.
     */
    protected function assertSaiqNotAuthenticated(): void
    {
        $this->assertFalse(
            session()->has('x'),
            'User is authenticated in SaiQ session.'
        );
    }

    /**
     * Assert audit log contains entry.
     *
     * @param string $action
     * @param array $where
     */
    protected function assertAuditLogged(string $action, array $where = []): void
    {
        $this->assertDatabaseHas('audit_log', array_merge([
            'action' => $action,
        ], $where));
    }

    /**
     * Ensure there is minimal base data for homologacion programa tests.
     *
     * @return array{periodo:string, programaPca:string, programaExt:string, plan:int}
     */
    protected function ensureHomologacionBaseData(): array
    {
        $periodo = DB::table('periodo')->where('activo', 1)->value('id');
        if (!$periodo) {
            $periodo = '2026-1';
            DB::table('periodo')->insert([
                'id' => $periodo,
                'nombre' => '2026-1',
                'activo' => 1,
            ]);
        }

        $programaPca = DB::table('programa')->where('enpca', 1)->where('activo', 1)->value('cod');
        if (!$programaPca) {
            $programaPca = 'PCA001';
            DB::table('programa')->insert([
                'cod' => $programaPca,
                'nombre' => 'Programa PCA',
                'nivel' => 1,
                'inst' => null,
                'enpca' => 1,
                'activo' => 1,
            ]);
        }

        $programaExt = DB::table('programa')->where('enpca', 0)->where('activo', 1)->value('cod');
        if (!$programaExt) {
            $programaExt = 'EXT001';
            DB::table('programa')->insert([
                'cod' => $programaExt,
                'nombre' => 'Programa Externo',
                'nivel' => 1,
                'inst' => null,
                'enpca' => 0,
                'activo' => 1,
            ]);
        }

        if (Schema::hasTable('programa_procedencia_relacion')) {
            DB::table('programa_procedencia_relacion')->updateOrInsert([
                'programa_ext_cod' => $programaExt,
                'programa_pca_cod' => $programaPca,
            ], [
                'institucion_id' => null,
                'nivel_origen' => null,
                'activo' => 1,
            ]);
        }

        $plan = DB::table('plan')->where('programa', $programaPca)->value('id');
        if (!$plan) {
            $plan = DB::table('plan')->insertGetId([
                'programa' => $programaPca,
                'num' => '2026',
                'vigencia' => null,
            ]);
        }

        // Asegura que el usuario de sesión usado en las pruebas de homologación exista.
        DB::table('usuario')->updateOrInsert([
            'id' => 'sistemas',
        ], [
            'clave' => password_hash('secret', PASSWORD_BCRYPT),
            'tipo' => 2,
            'programa' => $programaPca,
            'activo' => 1,
        ]);

        return [
            'periodo' => $periodo,
            'programaPca' => $programaPca,
            'programaExt' => $programaExt,
            'plan' => (int) $plan,
        ];
    }
}
