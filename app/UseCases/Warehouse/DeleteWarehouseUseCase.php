<?php

namespace App\UseCases\Warehouse;

use App\Exceptions\Business\ResourceInUseException;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * 倉庫の物理削除。条件は商品の物理削除と同じ
 * （履歴・倉庫間移動で使われておらず、在庫がない、またはすべて0）。
 * 使用済みの倉庫は、削除ではなく無効化する。
 */
class DeleteWarehouseUseCase
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly InventoryRepositoryInterface $inventories,
        private readonly StockMovementRepositoryInterface $movements,
        private readonly StockTransferRepositoryInterface $transfers,
    ) {}

    /**
     * @throws ModelNotFoundException 倉庫が存在しない
     * @throws ResourceInUseException 削除条件を満たさない
     */
    public function execute(int $warehouseId): void
    {
        DB::transaction(function () use ($warehouseId) {
            $warehouse = $this->warehouses->find($warehouseId);

            if ($warehouse === null) {
                throw (new ModelNotFoundException)->setModel(Warehouse::class, [$warehouseId]);
            }

            $inventories = $this->inventories->lockByWarehouseForUpdate($warehouseId);

            if ($inventories->contains(fn (Inventory $inventory) => $inventory->quantity > 0)) {
                throw new ResourceInUseException('warehouse', $warehouseId, '在庫が残っています。');
            }

            if ($this->movements->existsByWarehouse($warehouseId)) {
                throw new ResourceInUseException('warehouse', $warehouseId, '在庫変動履歴があります。');
            }

            if ($this->transfers->existsByWarehouse($warehouseId)) {
                throw new ResourceInUseException('warehouse', $warehouseId, '倉庫間移動で使用されています。');
            }

            $this->inventories->deleteZeroByWarehouse($warehouseId);
            $this->warehouses->delete($warehouse);
        }, self::MAX_ATTEMPTS);
    }
}
