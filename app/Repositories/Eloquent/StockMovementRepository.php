<?php

namespace App\Repositories\Eloquent;

use App\Models\StockMovement;
use App\Repositories\Contracts\StockMovementRepositoryInterface;

class StockMovementRepository implements StockMovementRepositoryInterface
{
    public function create(array $attributes): StockMovement
    {
        return StockMovement::create($attributes);
    }

    public function existsByProduct(int $productId): bool
    {
        return StockMovement::query()
            ->where('product_id', $productId)
            ->exists();
    }

    public function existsByWarehouse(int $warehouseId): bool
    {
        return StockMovement::query()
            ->where('warehouse_id', $warehouseId)
            ->exists();
    }
}
