<?php

namespace App\UseCases\Transfer;

use App\Exceptions\Business\BusinessRuleViolationException;
use App\Exceptions\Business\InactiveResourceException;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use App\Services\StockItemGuard;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * 倉庫間移動の申請。pending の移動を作るだけで、在庫はロックも変更もしない。
 * 在庫が動くのは、移動の完了のとき。
 */
class RequestTransferUseCase
{
    public function __construct(
        private readonly StockItemGuard $guard,
        private readonly WarehouseRepositoryInterface $warehouses,
        private readonly ProductRepositoryInterface $products,
        private readonly StockTransferRepositoryInterface $transfers,
    ) {}

    /**
     * @param  User  $requester  申請者
     * @param  array<int, mixed>  $items  各要素は ['product_id' => int, 'quantity' => int]
     *
     * @throws BusinessRuleViolationException 明細が不正、または移動元と移動先が同じ
     * @throws ModelNotFoundException 倉庫・商品が存在しない
     * @throws InactiveResourceException 倉庫・商品が無効
     */
    public function execute(User $requester, int $fromWarehouseId, int $toWarehouseId, array $items): StockTransfer
    {
        $items = $this->guard->normalize($items);

        if ($fromWarehouseId === $toWarehouseId) {
            throw new BusinessRuleViolationException(
                BusinessRuleViolationException::SAME_WAREHOUSE,
                '移動元と移動先に同じ倉庫は指定できません。',
            );
        }

        return DB::transaction(function () use ($requester, $fromWarehouseId, $toWarehouseId, $items) {
            $this->assertWarehousesUsable([$fromWarehouseId, $toWarehouseId]);
            $this->assertProductsUsable(array_column($items, 'product_id'));

            return $this->transfers->create([
                'from_warehouse_id' => $fromWarehouseId,
                'to_warehouse_id' => $toWarehouseId,
                'requested_by' => $requester->id,
            ], $items);
        });
    }

    /**
     * @param  list<int>  $warehouseIds
     */
    private function assertWarehousesUsable(array $warehouseIds): void
    {
        $warehouses = $this->warehouses->findManyByIds($warehouseIds);

        $missing = array_values(array_diff($warehouseIds, $warehouses->keys()->all()));

        if ($missing !== []) {
            throw (new ModelNotFoundException)->setModel(Warehouse::class, $missing);
        }

        $inactive = $warehouses
            ->filter(fn (Warehouse $warehouse) => ! $warehouse->is_active)
            ->keys()
            ->values()
            ->all();

        if ($inactive !== []) {
            throw new InactiveResourceException(InactiveResourceException::WAREHOUSE, $inactive);
        }
    }

    /**
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
