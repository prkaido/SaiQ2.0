<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TalentoHumanoPensumTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function talento_humano_pensum_is_registered_with_university_cycle_relation(): void
    {
        $planTecnologia = DB::table('plan')->where('programa', '613')->where('num', '61301')->first();
        $planProfesional = DB::table('plan')->where('programa', '614')->where('num', '61401')->first();

        $this->assertNotNull($planTecnologia);
        $this->assertNotNull($planProfesional);

        $this->assertDatabaseHas('programa', [
            'cod' => '613',
            'nombre' => 'TECNOLOGIA EN GESTION HUMANA',
            'nivel' => 2,
            'enpca' => 1,
            'activo' => 1,
        ]);
        $this->assertDatabaseHas('programa', [
            'cod' => '614',
            'nombre' => 'ADMINISTRACION DEL TALENTO HUMANO',
            'nivel' => 3,
            'enpca' => 1,
            'activo' => 1,
        ]);

        $this->assertSame(30, DB::table('asignatura')->where('programa', '613')->where('plan', $planTecnologia->id)->count());
        $this->assertSame(22, DB::table('asignatura')->where('programa', '614')->where('plan', $planProfesional->id)->count());

        $this->assertDatabaseHas('asignatura', [
            'cod' => '613002',
            'programa' => '613',
            'plan' => $planTecnologia->id,
            'nivel' => 1,
            'creditos' => '4',
            'ihsemana' => 4,
        ]);
        $this->assertDatabaseHas('asignatura', [
            'cod' => '614017',
            'programa' => '614',
            'plan' => $planProfesional->id,
            'nivel' => 9,
            'creditos' => '4',
            'ihsemana' => 4,
        ]);
        $this->assertDatabaseHas('progrel', [
            'dir' => '613',
            'prog' => '614',
            'activo' => 1,
        ]);
    }
}
