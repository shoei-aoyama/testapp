<?php

namespace App\Repositories\Eloquent;

use App\Models\Inventory;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class InventoryRepository implements InventoryRepositoryInterface
{
    public function insertMissing(array $pairs): void
    {
        $pairs = $this->normalize($pairs);

        if ($pairs === []) {
            return;
        }

        $now = now();

        $rows = array_map(fn (array $pair) => [
            'product_id' => $pair['product_id'],
            'warehouse_id' => $pair['warehouse_id'],
            'quantity' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ], $pairs);

        // すでに行がある組は無視される（UNIQUE(product_id, warehouse_id) による）。
        // 商品・倉庫の存在確認は、呼び出し側（UseCase）で済ませておくこと。
        Inventory::insertOrIgnore($rows);
    }

    public function lockForUpdate(array $pairs): Collection
    {
        $pairs = $this->normalize($pairs);

        if ($pairs === []) {
            return new Collection;
        }

        return Inventory::query()
            ->where(function (Builder $query) use ($pairs) {
                foreach ($pairs as $pair) {
                    $query->orWhere(function (Builder $condition) use ($pair) {
                        $condition
                            ->where('product_id', $pair['product_id'])
                            ->where('warehouse_id', $pair['warehouse_id']);
                    });
                }
            })
            ->orderBy('product_id')
            ->orderBy('warehouse_id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (Inventory $inventory) => $inventory->product_id.':'.$inventory->warehouse_id);
    }

    public function updateQuantity(Inventory $inventory, int $quantity): void
    {
        $inventory->quantity = $quantity;
        $inventory->save();
    }

    public function lockByProductForUpdate(int $productId): Collection
    {
        return Inventory::query()
            ->where('product_id', $productId)
            ->orderBy('warehouse_id')
            ->lockForUpdate()
            ->get();
    }

    public function lockByWarehouseForUpdate(int $warehouseId): Collection
    {
        return Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->orderBy('product_id')
            ->lockForUpdate()
            ->get();
    }

    public function deleteZeroByProduct(int $productId): void
    {
        Inventory::query()
            ->where('product_id', $productId)
            ->where('quantity', 0)
            ->delete();
    }

    public function deleteZeroByWarehouse(int $warehouseId): void
    {
        Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', 0)
            ->delete();
    }

    public function existsNonZeroByProduct(int $productId): bool
    {
        return Inventory::query()
            ->where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->exists();
    }

    public function existsNonZeroByWarehouse(int $warehouseId): bool
    {
        return Inventory::query()
            ->where('warehouse_id', $warehouseId)
            ->where('quantity', '>', 0)
            ->exists();
    }

    /**
     * 組の重複を取り除き、product_id 昇順、次に warehouse_id 昇順に並べる。
     * ロックの取得順を統一するため（デッドロックの予防）。
     *
     * @param  list<array{product_id: int, warehouse_id: int}>  $pairs
     * @return list<array{product_id: int, warehouse_id: int}>
     */
    private function normalize(array $pairs): array
    {
        $unique = [];

        foreach ($pairs as $pair) {
            $productId = (int) $pair['product_id'];
            $warehouseId = (int) $pair['warehouse_id'];

            $unique[$productId.':'.$warehouseId] = [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
            ];
        }

        $sorted = array_values($unique);

        usort($sorted, fn (array $a, array $b) => [$a['product_id'], $a['warehouse_id']] <=> [$b['product_id'], $b['warehouse_id']]);

        return $sorted;
    }
}
