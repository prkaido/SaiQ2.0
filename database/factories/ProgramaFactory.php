<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProgramaFactory extends Factory
{
    protected $model = \App\Models\Programa::class;

    public function definition(): array
    {
        return [
            'nombre' => $this->faker->word() . ' ' . $this->faker->word(),
            'enpca' => $this->faker->boolean(),
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function pca(): static
    {
        return $this->state(fn (array $attributes) => [
            'enpca' => 1,
        ]);
    }

    public function externa(): static
    {
        return $this->state(fn (array $attributes) => [
            'enpca' => 0,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => 0,
        ]);
    }
}
