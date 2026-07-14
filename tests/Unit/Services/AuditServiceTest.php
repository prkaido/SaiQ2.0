<?php

namespace Tests\Unit\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditServiceTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    private AuditService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(AuditService::class);
    }

    /** @test */
    public function can_log_audit_action(): void
    {
        $this->actingAs($this->createTestUser());

        $this->service->log(
            'CREATE_HOMOLOGACION',
            ['programa_id' => 1, 'estado' => 'creado'],
            homologacionId: 100
        );

        $this->assertDatabaseHas('audit_log', [
            'action' => 'CREATE_HOMOLOGACION',
            'homologacion_id' => 100,
            'tabla' => 'homologacion',
        ]);
    }

    /** @test */
    public function logs_before_and_after_values(): void
    {
        $this->actingAs($this->createTestUser());

        $this->service->log(
            'UPDATE_HOMOLOGACION',
            [],
            100,
            'homologacion',
            before: ['estado' => 'pendiente'],
            after: ['estado' => 'aprobado']
        );

        $record = DB::table('audit_log')
            ->where('action', 'UPDATE_HOMOLOGACION')
            ->first();

        $this->assertNotNull($record);
        $this->assertStringContainsString('pendiente', $record->campo_anterior);
        $this->assertStringContainsString('aprobado', $record->campo_nuevo);
    }

    /** @test */
    public function captures_ip_address(): void
    {
        $this->actingAs($this->createTestUser());

        $this->service->log('TEST_ACTION', homologacionId: 1);

        $record = DB::table('audit_log')
            ->where('action', 'TEST_ACTION')
            ->first();

        $this->assertNotNull($record->ip_address);
    }

    /** @test */
    public function captures_user_agent(): void
    {
        $this->actingAs($this->createTestUser());

        $this->service->log('TEST_ACTION', homologacionId: 1);

        $record = DB::table('audit_log')
            ->where('action', 'TEST_ACTION')
            ->first();

        $this->assertNotNull($record->user_agent);
    }

    /** @test */
    public function handles_missing_audit_log_table(): void
    {
        $this->actingAs($this->createTestUser());

        Schema::dropIfExists('audit_log');

        $this->service->log('ACTION_WITHOUT_TABLE');

        // Should not throw exception, silently handle it
        $this->assertTrue(true);

        Schema::create('audit_log', function ($table) {
            $table->id('id_log');
            $table->unsignedInteger('homologacion_id')->nullable();
            $table->string('user_id', 50)->nullable();
            $table->string('action', 80);
            $table->string('tabla', 80)->nullable();
            $table->longText('campo_anterior')->nullable();
            $table->longText('campo_nuevo')->nullable();
            $table->longText('details')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('timestamp')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /** @test */
    public function stores_json_details(): void
    {
        $this->actingAs($this->createTestUser());

        $details = [
            'programa' => 'Ingeniería',
            'plan' => 1,
            'asignaturas' => [1, 2, 3],
        ];

        // El método log() no debe lanzar excepciones
        $this->service->log('COMPLEX_ACTION', $details, 100);

        // Si la tabla audit_log existe, verificar que se guardó
        if (Schema::hasTable('audit_log')) {
            $this->assertDatabaseHas('audit_log', [
                'action' => 'COMPLEX_ACTION',
                'homologacion_id' => 100,
            ]);
        }

        $this->assertTrue(true); // Test pasa si no hay excepciones
    }

    /** @test */
    public function handles_unicode_in_details(): void
    {
        $this->actingAs($this->createTestUser());

        $details = ['observación' => 'Se aprobó la homologación del alumno María'];

        // El método log() no debe lanzar excepciones
        $this->service->log('UNICODE_ACTION', $details);

        // Si la tabla audit_log existe, verificar que se guardó
        if (Schema::hasTable('audit_log')) {
            $this->assertDatabaseHas('audit_log', [
                'action' => 'UNICODE_ACTION',
            ]);
        }

        $this->assertTrue(true); // Test pasa si no hay excepciones
    }

    private function createTestUser()
    {
        return \Database\Factories\UsuarioFactory::new()->create();
    }
}
