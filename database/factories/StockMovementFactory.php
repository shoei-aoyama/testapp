<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\StockMovement>
 *
 * 保証すること: 単体でDB制約を満たす在庫変動履歴を1件作る。
 *               既定は IN（数量1以上、移動明細なし）。変更前後の数量は種別と数量に合う。
 *               quantity だけを上書きした場合も、変更後（IN系）/変更前（OUT系）は数量に合わせて決まる。
 * 保証しないこと: inventories は更新しない。つまり履歴と現在庫の整合は保証しない。
 *               数量まわりを上書きするときは、quantity / quantity_before / quantity_after の3つを
 *               セットで指定する（食い違うとCHECK制約でエラーになる）。
 *
 * 状態:
 *   out() / adjustmentIn() / adjustmentOut()
 *   transferIn(?StockTransferItem) / transferOut(?StockTransferItem)
 *
 * transferIn / transferOut について:
 *   - 移動明細を渡さない場合は、completed のヘッダに属する明細を1件作って紐づける。
 *   - 商品は明細の商品、倉庫は TRANSFER_OUT なら移動元、TRANSFER_IN なら移動先になる。
 *   - 数量は明細の数量とは連動しない。
 *   - 明細を渡さない場合は、履歴1件ごとに新しい明細を作る。
 *     1つの明細に紐づく TRANSFER_OUT / TRANSFER_IN は各1件までなので、
 *     同じ明細に OUT と IN の両方を作りたいときは、同じ明細を両方に渡す。
 */
class StockMovementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'warehouse_id' => Warehouse::factory(),
            'type' => StockMovementType::In,
            'quantity' => fake()->numberBetween(1, 100),
            'quantity_before' => fake()->numberBetween(0, 100),
            // 増加系: 変更後 = 変更前 + 数量（上書きされた値を使うため、クロージャで決める）
            'quantity_after' => fn (array $attributes) => $attributes['quantity_before'] + $attributes['quantity'],
            'reason' => null,
            'user_id' => User::factory(),
            'stock_transfer_item_id' => null,
        ];
    }

    public function out(): static
    {
        return $this->decrease(StockMovementType::Out);
    }

    public function adjustmentIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovementType::AdjustmentIn,
        ]);
    }

    public function adjustmentOut(): static
    {
        return $this->decrease(StockMovementType::AdjustmentOut);
    }

    /**
     * 倉庫間移動による増加（移動先の倉庫の履歴）。
     */
    public function transferIn(?StockTransferItem $item = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => StockMovementType::TransferIn,
        ] + $this->transferAttributes($item, 'to_warehouse_id'));
    }

    /**
     * 倉庫間移動による減少（移動元の倉庫の履歴）。
     */
    public function transferOut(?StockTransferItem $item = null): static
    {
        return $this->decrease(StockMovementType::TransferOut)
            ->state(fn (array $attributes) => $this->transferAttributes($item, 'from_warehouse_id'));
    }

    /**
     * 減少系の履歴にする。変更後の数量を先に決め、変更前 = 変更後 + 数量 とする。
     */
    private function decrease(StockMovementType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
            'quantity_after' => fake()->numberBetween(0, 100),
            'quantity_before' => fn (array $attributes) => $attributes['quantity_after'] + $attributes['quantity'],
        ]);
    }

    /**
     * 移動明細に紐づく属性（明細ID・商品・倉庫）を返す。
     *
     * @return array<string, int>
     */
    private function transferAttributes(?StockTransferItem $item, string $warehouseColumn): array
    {
        $item ??= StockTransferItem::factory()
            ->state(['stock_transfer_id' => StockTransfer::factory()->completed()])
            ->create();

        return [
            'stock_transfer_item_id' => $item->id,
            'product_id' => $item->product_id,
            'warehouse_id' => $item->stockTransfer->{$warehouseColumn},
        ];
    }
}
