<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Todo>
 */
class TodoFactory extends Factory
{
  public function definition(): array
  {
    return [
      'title' => fake()->sentence(),
      'is_completed' => false,
    ];
  }
}