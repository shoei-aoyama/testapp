<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Product>
 *
 * 保証すること: 単体でDB制約を満たす、有効な商品を1件作る（商品コードは重複しない）。
 *               カテゴリを指定しない場合は、新しいカテゴリも1件作る。
 *               単価は約2割の確率でNULLになる。
 * 保証しないこと: 在庫・履歴は作らない。
 * 状態: inactive()
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $names = ['ボルト', 'ナット', '段ボール', 'ラベル', '作業手袋', 'テープ', 'ファイル', '電池', 'ケーブル', 'タオル'];

        return [
            'code' => 'P-'.fake()->unique()->numerify('######'),
            'name' => fake()->randomElement($names).' '.fake()->bothify('??-###'),
            'category_id' => Category::factory(),
            'unit_price' => fake()->optional(0.8)->numberBetween(100, 50000),
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
