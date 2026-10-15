<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\StockTransferItem>
 *
 * 保証すること: 単体でDB制約を満たす移動明細を1件作る（数量は1以上）。
 *               移動・商品を指定しない場合は、それぞれ新しく1件作る（移動は pending のヘッダ）。
 * 保証しないこと: 在庫変動履歴は作らない。
 *               同じ移動に同じ商品の明細を2件作るとユニーク制約でエラーになる（意図した動作）。
 */
class StockTransferItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stock_transfer_id' => StockTransfer::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 50),
        ];
    }
}
