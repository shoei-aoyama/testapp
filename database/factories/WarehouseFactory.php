<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Warehouse>
 *
 * 保証すること: 単体でDB制約を満たす、有効な倉庫を1件作る（倉庫コードは重複しない）。
 *               郵便番号・住所・電話番号は値が入る（NULLのケースは明示的に指定する）。
 * 保証しないこと: 在庫・履歴は作らない。
 * 状態: inactive()
 */
class WarehouseFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'WH-'.fake()->unique()->numerify('######'),
            'name' => fake()->numerify('テスト倉庫-####'),
            'postal_code' => fake()->numerify('###-####'),
            'address' => fake()->numerify('テスト県テスト市テスト町#-#-#'),
            'phone' => fake()->numerify('0##-####-####'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
