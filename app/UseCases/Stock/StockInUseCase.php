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
use LogicException;

/**
 * 入庫: 1つの倉庫に、複数の商品をまとめて入庫する。
 *
 * 途中で1件でも失敗したら、すべての変更を取り消す（トランザクション）。
 */
class StockInUseCase
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
     * @param  int  $warehouseId  入庫先の倉庫
     * @param  array<int, mixed>  $items  各要素は ['product_id' => int, 'quantity' => int]
     * @param  string|null  $reason  理由（任意。空文字はNULLとして扱う）
     * @return Collection<int, StockMovement>  作成した履歴
     *
     * @throws \App\Exceptions\Business\BusinessRuleViolationException 明細が不正（空・数量・重複）
     * @throws ModelNotFoundException 倉庫・商品が存在しない
     * @throws InactiveResourceException 倉庫・商品が無効
     */
    public function execute(User $executor, int $warehouseId, array $items, ?string $reason = null): Collection
    {
        $items = $this->guard->normalize($items);

        // ロックの取得順と、更新の順を、商品ID昇順にそろえる
        usort($items, fn (array $a, array $b) => $a['product_id'] <=> $b['product_id']);

        $reason = ($reason === null || trim($reason) === '') ? null : $reason;

        return DB::transaction(function () use ($executor, $warehouseId, $items, $reason) {
            $this->assertWarehouseUsable($warehouseId);
            $this->assertProductsUsable(array_column($items, 'product_id'));

            $pairs = array_map(
                fn (array $item) => ['product_id' => $item['product_id'], 'warehouse_id' => $warehouseId],
                $items,
            );

            // 行がなければ作成を試みてから、ロックする（同時の初回入庫でもエラーにならない）
            $this->inventories->insertMissing($pairs);
            $locked = $this->inventories->lockForUpdate($pairs);

            $created = collect();

            foreach ($items as $item) {
                $inventory = $locked->get($item['product_id'].':'.$warehouseId);

                if ($inventory === null) {
                    // 作成を試みた直後なので、通常は起こらない
                    throw new LogicException(sprintf(
                        '在庫行が見つかりません（商品ID: %d、倉庫ID: %d）。',
                        $item['product_id'],
                        $warehouseId,
                    ));
                }

                $before = $inventory->quantity;
                $after = $before + $item['quantity'];

                $this->inventories->updateQuantity($inventory, $after);

                $created->push($this->movements->create([
                    'product_id' => $item['product_id'],
                    'warehouse_id' => $warehouseId,
                    'type' => StockMovementType::In,
                    'quantity' => $item['quantity'],
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reason' => $reason,
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
