<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class UsuarioFactory extends Factory
{
    protected $model = \App\Models\Usuario::class;

    public function definition(): array
    {
        static $count = 1;
        
        return [
            'id' => 'usuario_' . $count++,
            'nombre' => $this->faker->firstName(),
            'apellido' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'clave' => Hash::make('password123'),
            'tipo' => $this->faker->randomElement([1, 2, 3]),
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 1,
        ]);
    }

    public function director(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 2,
        ]);
    }

    public function estudiante(): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo' => 3,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'activo' => 0,
        ]);
    }
}
