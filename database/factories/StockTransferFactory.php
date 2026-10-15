<?php

namespace Database\Factories;

use App\Enums\StockTransferStatus;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\StockTransfer>
 *
 * 保証すること: 単体でDB制約を満たす倉庫間移動の「ヘッダ」を1件作る。
 *               移動元と移動先は別々の倉庫になる。ステータスと実行者・日時の組み合わせはCHECK制約に合う。
 * 保証しないこと: 明細（stock_transfer_items）・在庫変動履歴・在庫の増減は一切作らない。
 *               特に completed() は「ヘッダを完了状態にするだけ」であり、
 *               業務的に完全な完了済み移動（明細 + TRANSFER_OUT/IN + 在庫更新）ではない。
 *               整合した完了済み移動が必要なときは、Seeder（DummyDataSeeder）や
 *               テスト用Helperで、明細・履歴・在庫までセットで作る。
 * 状態: pending()（既定） / completed() / cancelled()
 */
class StockTransferFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory(),
            'status' => StockTransferStatus::Pending,
            'requested_by' => User::factory(),
            'completed_by' => null,
            'completed_at' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransferStatus::Pending,
            'completed_by' => null,
            'completed_at' => null,
            'cancelled_by' => null,
            'cancelled_at' => null,
        ]);
    }

    /**
     * ヘッダを完了状態にするだけ。明細・履歴・在庫は作らない。
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransferStatus::Completed,
            'completed_by' => User::factory()->manager(),
            'completed_at' => now(),
            'cancelled_by' => null,
            'cancelled_at' => null,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => StockTransferStatus::Cancelled,
            'completed_by' => null,
            'completed_at' => null,
            'cancelled_by' => User::factory()->manager(),
            'cancelled_at' => now(),
        ]);
    }
}
