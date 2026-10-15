<?php

namespace App\Repositories\Contracts;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;

/**
 * 現在庫（inventories）のデータアクセス。
 *
 * 「（商品ID, 倉庫ID）の組」は list<array{product_id: int, warehouse_id: int}> で表す。
 *
 * 名前が ...ForUpdate で終わるメソッドは、必ずトランザクション内で使うこと。
 * トランザクション外で呼ぶとロックがすぐ解放され、ロックの意味がなくなる。
 */
interface InventoryRepositoryInterface
{
    /**
     * 組のうち、行が存在しないものを在庫0で作成する。すでにあるものは何もしない。
     * 同時に同じ組を作ろうとしても、エラーにならない。
     *
     * @param  list<array{product_id: int, warehouse_id: int}>  $pairs
     */
    public function insertMissing(array $pairs): void;

    /**
     * 組に該当する行を、product_id 昇順、次に warehouse_id 昇順でロックして返す。
     * 存在しない組は含まれない（在庫0として扱うのは呼び出し側）。
     * 戻り値は「{product_id}:{warehouse_id}」をキーにしたコレクション。
     *
     * トランザクション内で使うこと。
     *
     * @param  list<array{product_id: int, warehouse_id: int}>  $pairs
     * @return Collection<string, Inventory>
     */
    public function lockForUpdate(array $pairs): Collection;

    /**
     * ロック済みの行の数量を更新する。
     */
    public function updateQuantity(Inventory $inventory, int $quantity): void;

    /**
     * 商品の在庫行をすべてロックして返す（warehouse_id 昇順）。商品の物理削除の判定に使う。
     *
     * トランザクション内で使うこと。
     *
     * @return Collection<int, Inventory>
     */
    public function lockByProductForUpdate(int $productId): Collection;

    /**
     * 倉庫の在庫行をすべてロックして返す（product_id 昇順）。倉庫の物理削除の判定に使う。
     *
     * トランザクション内で使うこと。
     *
     * @return Collection<int, Inventory>
     */
    public function lockByWarehouseForUpdate(int $warehouseId): Collection;

    /**
     * 商品の在庫行のうち、数量が0のものを削除する。
     */
    public function deleteZeroByProduct(int $productId): void;

    /**
     * 倉庫の在庫行のうち、数量が0のものを削除する。
     */
    public function deleteZeroByWarehouse(int $warehouseId): void;

    /**
     * 商品に、数量が1以上の在庫行があるか。
     */
    public function existsNonZeroByProduct(int $productId): bool;

    /**
     * 倉庫に、数量が1以上の在庫行があるか。
     */
    public function existsNonZeroByWarehouse(int $warehouseId): bool;
}
