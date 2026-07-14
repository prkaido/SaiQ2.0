<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InstitucionFactory extends Factory
{
    protected $model = \App\Models\Institucion::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->company(),
            'ciudad' => $this->faker->city(),
            'pais' => $this->faker->country(),
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
