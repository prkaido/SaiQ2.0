<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AsignaturaFactory extends Factory
{
    protected $model = \App\Models\Asignatura::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->word(),
            'codigo' => strtoupper($this->faker->unique()->lexify('???###')),
            'creditos' => $this->faker->numberBetween(1, 5),
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => 0,
        ]);
    }
}
