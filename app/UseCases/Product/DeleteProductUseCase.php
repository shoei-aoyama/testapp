<?php

namespace App\UseCases\Product;

use App\Exceptions\Business\ResourceInUseException;
use App\Models\Product;
use App\Models\Inventory;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * 商品の物理削除。
 *
 * 次の条件をすべて満たす場合だけ削除できる。
 *   - 在庫変動履歴で使われていない
 *   - 倉庫間移動で使われていない
 *   - 現在庫がない（在庫行がない、またはすべて0）
 * 満たさない場合は ResourceInUseException。使用済みの商品は、削除ではなく無効化する。
 *
 * 在庫行をロックしてから条件を確認し、数量0の行を先に削除してから商品を削除する（同一トランザクション）。
 */
class DeleteProductUseCase
{
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly InventoryRepositoryInterface $inventories,
        private readonly StockMovementRepositoryInterface $movements,
        private readonly StockTransferRepositoryInterface $transfers,
    ) {}

    /**
     * @throws ModelNotFoundException 商品が存在しない
     * @throws ResourceInUseException 削除条件を満たさない
     */
    public function execute(int $productId): void
    {
        DB::transaction(function () use ($productId) {
            $product = $this->products->find($productId);

            if ($product === null) {
                throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);
            }

            // 在庫行をロックしてから、在庫の有無を確認する
            $inventories = $this->inventories->lockByProductForUpdate($productId);

            if ($inventories->contains(fn (Inventory $inventory) => $inventory->quantity > 0)) {
                throw new ResourceInUseException('product', $productId, '在庫が残っています。');
            }

            if ($this->movements->existsByProduct($productId)) {
                throw new ResourceInUseException('product', $productId, '在庫変動履歴があります。');
            }

            if ($this->transfers->existsByProduct($productId)) {
                throw new ResourceInUseException('product', $productId, '倉庫間移動で使用されています。');
            }

            $this->inventories->deleteZeroByProduct($productId);
            $this->products->delete($product);
        }, self::MAX_ATTEMPTS);
    }
}
