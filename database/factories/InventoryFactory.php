<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Inventory>
 *
 * 保証すること: 単体でDB制約を満たす在庫行を1件作る。
 *               商品・倉庫を指定しない場合は、それぞれ新しく1件作る。
 * 保証しないこと: 在庫変動履歴は作らない。つまり「履歴の合計と現在庫が一致する」ことは保証しない。
 *               履歴と整合した在庫が必要なテストでは、履歴も一緒に作る。
 */
class InventoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'quantity' => fake()->numberBetween(0, 500),
        ];
    }
}
