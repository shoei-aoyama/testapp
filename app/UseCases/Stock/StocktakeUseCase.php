<?php

namespace App\UseCases\Stock;

use App\Enums\StockMovementType;
use App\Exceptions\Business\InactiveResourceException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Services\StockItemGuard;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 棚卸調整: 1つの倉庫について、実在庫を入力し、現在庫との差分を調整する。
 *
 * - 差分がない商品は、何もしない（履歴も作らない）
 * - 実在庫が多ければ ADJUSTMENT_IN、少なければ ADJUSTMENT_OUT（数量は差分）
 * - 実在庫0で在庫行がない商品は、行を作らない
 * - 変更が1件もなくても成功とし、空のコレクションを返す
 */
class StocktakeUseCase
{
    /** デッドロックで失敗したときの、最大の試行回数 */
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly StockItemGuard $guard,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly ProductRepositoryInterface $products,
        private readonly InventoryRepositoryInterface $inventories,
        private readonly StockMovementRepositoryInterface $movements,
    ) {}

    /**
     * @param  User  $executor  実行者
     * @param  int  $warehouseId  棚卸をする倉庫
     * @param  array<int, mixed>  $items  各要素は ['product_id' => int, 'actual_quantity' => int（0以上）]
     * @return Collection<int, StockMovement>  作成した履歴（差分のあった商品のみ）
     *
     * @throws \App\Exceptions\Business\BusinessRuleViolationException 明細が不正（空・数量・重複）
     * @throws ModelNotFoundException 倉庫・商品が存在しない
     * @throws InactiveResourceException 倉庫・商品が無効
     */
    public function execute(User $executor, int $warehouseId, array $items): Collection
    {
        $items = $this->guard->normalize($items, 'actual_quantity', 0);

        // ロックの取得順と、更新の順を、商品ID昇順にそろえる
        usort($items, fn (array $a, array $b) => $a['product_id'] <=> $b['product_id']);

        return DB::transaction(function () use ($executor, $warehouseId, $items) {
            $this->assertWarehouseUsable($warehouseId);
            $this->assertProductsUsable(array_column($items, 'product_id'));

            $toPair = fn (array $item) => ['product_id' => $item['product_id'], 'warehouse_id' => $warehouseId];

            // 実在庫が1以上の商品だけ、行がなければ作成する（実在庫0の商品の行は作らない）
            $positive = array_values(array_filter($items, fn (array $item) => $item['actual_quantity'] > 0));

            if ($positive !== []) {
                $this->inventories->insertMissing(array_map($toPair, $positive));
            }

            // 全商品の行をロックする（存在しない行は含まれず、在庫0として扱う）
            $locked = $this->inventories->lockForUpdate(array_map($toPair, $items));

            $created = collect();

            foreach ($items as $item) {
                $inventory = $locked->get($item['product_id'].':'.$warehouseId);

                $before = $inventory?->quantity ?? 0;
                $actual = $item['actual_quantity'];
                $difference = $actual - $before;

                if ($difference === 0) {
                    continue;
                }

                // 差分があるのは、行がある場合のみ（行がない = 在庫0 = 実在庫0なら差分0）
                $this->inventories->updateQuantity($inventory, $actual);

                $created->push($this->movements->create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'type' => $difference > 0 ? StockMovementType::AdjustmentIn : StockMovementType::AdjustmentOut,
                    'quantity' => abs($difference),
                    'quantity_before' => $before,
                    'quantity_after' => $actual,
                    'reason' => null,
                    'user_id' => $executor->id,
                    'stock_transfer_item_id' => null,
                ]));
            }

            return $created;
        }, self::MAX_ATTEMPTS);
    }

    /**
     * 倉庫が存在し、有効であること。
     */
    private function assertWarehouseUsable(int $warehouseId): void
    {
        $warehouse = $this->warehouses->find($warehouseId);

        if ($warehouse === null) {
            throw (new ModelNotFoundException)->setModel(Warehouse::class, [$warehouseId]);
        }

        if (! $warehouse->is_active) {
            throw new InactiveResourceException(InactiveResourceException::WAREHOUSE, [$warehouseId]);
        }
    }

    /**
     * 商品がすべて存在し、有効であること。
     *
     * @param  list<int>  $productIds
     */
    private function assertProductsUsable(array $productIds): void
    {
        $products = $this->products->findManyByIds($productIds);

        $missing = array_values(array_diff($productIds, $products->keys()->all()));

        if ($missing !== []) {
            throw (new ModelNotFoundException)->setModel(Product::class, $missing);
        }

        $inactive = $products
            ->filter(fn (Product $product) => ! $product->is_active)
            ->keys()
            ->values()
            ->all();

        if ($inactive !== []) {
            throw new InactiveResourceException(InactiveResourceException::PRODUCT, $inactive);
        }
    }
}
