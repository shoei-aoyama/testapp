<?php

namespace App\Repositories\Contracts;

use App\Models\StockMovement;

/**
 * 在庫変動履歴（stock_movements）のデータアクセス。
 * 履歴は追記のみで、更新・削除は行わない。
 */
interface StockMovementRepositoryInterface
{
    /**
     * 履歴を1件作成する。
     *
     * @param  array{
     *     product_id: int,
     *     warehouse_id: int,
     *     type: \App\Enums\StockMovementType,
     *     quantity: int,
     *     quantity_before: int,
     *     quantity_after: int,
     *     reason?: string|null,
     *     user_id: int,
     *     stock_transfer_item_id?: int|null,
     * }  $attributes
     */
    public function create(array $attributes): StockMovement;

    /**
     * 商品の履歴が1件でもあるか（商品の物理削除の判定に使う）。
     */
    public function existsByProduct(int $productId): bool;

    /**
     * 倉庫の履歴が1件でもあるか（倉庫の物理削除の判定に使う）。
     */
    public function existsByWarehouse(int $warehouseId): bool;
}
