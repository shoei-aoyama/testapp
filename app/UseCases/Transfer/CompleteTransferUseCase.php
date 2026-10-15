<?php

namespace App\UseCases\Transfer;

use App\Enums\StockMovementType;
use App\Enums\StockTransferStatus;
use App\Exceptions\Business\InactiveResourceException;
use App\Exceptions\Business\InsufficientStockException;
use App\Exceptions\Business\InvalidStateTransitionException;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Repositories\Contracts\InventoryRepositoryInterface;
use App\Repositories\Contracts\StockMovementRepositoryInterface;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * 倉庫間移動の完了。移動元の在庫を減らし、移動先の在庫を増やす。
 *
 * ロック順: ① 移動のヘッダ → ② 在庫（商品ID昇順、次に倉庫ID昇順）。
 * 完了の二重実行は、ヘッダのロックと状態チェックで止まる。
 */
class CompleteTransferUseCase
{
    /** デッドロックで失敗したときの、最大の試行回数 */
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly StockTransferRepositoryInterface $transfers,
        private readonly InventoryRepositoryInterface $inventories,
        private readonly StockMovementRepositoryInterface $movements,
    ) {}

    /**
     * @param  User  $executor  実行者
     *
     * @throws ModelNotFoundException 移動が存在しない
     * @throws InvalidStateTransitionException pending でない
     * @throws InactiveResourceException 倉庫・商品に無効なものがある
     * @throws InsufficientStockException 移動元の在庫が足りない（不足した商品すべてを含む）
     */
    public function execute(User $executor, int $transferId): StockTransfer
    {
        return DB::transaction(function () use ($executor, $transferId) {
            $transfer = $this->transfers->findForUpdate($transferId);

            if ($transfer === null) {
                throw (new ModelNotFoundException)->setModel(StockTransfer::class, [$transferId]);
            }

            if ($transfer->status !== StockTransferStatus::Pending) {
                throw new InvalidStateTransitionException($transfer->status, StockTransferStatus::Completed);
            }

            $transfer = $this->transfers->loadDetails($transfer);

            $this->assertUsable($transfer);

            $fromId = $transfer->from_warehouse_id;
            $toId = $transfer->to_warehouse_id;

            // 商品ID昇順にそろえる（ロックと更新の順序）
            $items = $transfer->items->sortBy('product_id')->values();

            $fromPairs = [];
            $toPairs = [];

            foreach ($items as $item) {
                $fromPairs[] = ['product_id' => $item->product_id, 'warehouse_id' => $fromId];
                $toPairs[] = ['product_id' => $item->product_id, 'warehouse_id' => $toId];
            }

            // 行を作るのは移動先だけ。移動元に行がなければ在庫0として扱う
            $this->inventories->insertMissing($toPairs);
            $locked = $this->inventories->lockForUpdate([...$fromPairs, ...$toPairs]);

            // 先に全明細の移動元の在庫を確認し、不足をまとめて報告する
            $shortages = [];

            foreach ($items as $item) {
                $available = $locked->get($item->product_id.':'.$fromId)?->quantity ?? 0;

                if ($available < $item->quantity) {
                    $shortages[] = [
                        'product_id' => $item->product_id,
                        'warehouse_id' => $fromId,
                        'requested' => $item->quantity,
                        'available' => $available,
                    ];
                }
            }

            if ($shortages !== []) {
                throw new InsufficientStockException($shortages);
            }

            foreach ($items as $item) {
                $this->moveItem($executor, $item, $fromId, $toId, $locked);
            }

            $this->transfers->markCompleted($transfer, $executor->id, now());

            return $transfer;
        }, self::MAX_ATTEMPTS);
    }

    /**
     * 倉庫・商品がすべて有効であること（1つでも無効なら完了できない）。
     */
    private function assertUsable(StockTransfer $transfer): void
    {
        $warehouses = collect([$transfer->fromWarehouse, $transfer->toWarehouse])
            ->filter(fn ($warehouse) => ! $warehouse->is_active);

        if ($warehouses->isNotEmpty()) {
            throw new InactiveResourceException(
                InactiveResourceException::WAREHOUSE,
                $warehouses->pluck('id')->values()->all(),
            );
        }

        $products = $transfer->items
            ->map(fn (StockTransferItem $item) => $item->product)
            ->filter(fn ($product) => ! $product->is_active);

        if ($products->isNotEmpty()) {
            throw new InactiveResourceException(
                InactiveResourceException::PRODUCT,
                $products->pluck('id')->sort()->values()->all(),
            );
        }
    }

    /**
     * 1明細ぶんの移動: 移動元を減らして TRANSFER_OUT、移動先を増やして TRANSFER_IN の履歴を作る。
     */
    private function moveItem(
        User $executor,
        StockTransferItem $item,
        int $fromId,
        int $toId,
        \Illuminate\Support\Collection $locked,
    ): void {
        $from = $locked->get($item->product_id.':'.$fromId);
        $to = $locked->get($item->product_id.':'.$toId);

        if ($from === null || $to === null) {
            // 不足確認を通過し、移動先は作成を試みた直後なので、通常は起こらない
            throw new LogicException(sprintf(
                '在庫行が見つかりません（商品ID: %d）。',
                $item->product_id,
            ));
        }

        $fromBefore = $from->quantity;
        $fromAfter = $fromBefore - $item->quantity;
        $toBefore = $to->quantity;
        $toAfter = $toBefore + $item->quantity;

        $this->inventories->updateQuantity($from, $fromAfter);
        $this->movements->create([
            'product_id' => $item->product_id,
            'warehouse_id' => $fromId,
            'type' => StockMovementType::TransferOut,
            'quantity' => $item->quantity,
            'quantity_before' => $fromBefore,
            'quantity_after' => $fromAfter,
            'reason' => null,
            'user_id' => $executor->id,
            'stock_transfer_item_id' => $item->id,
        ]);

        $this->inventories->updateQuantity($to, $toAfter);
        $this->movements->create([
            'product_id' => $item->product_id,
            'warehouse_id' => $toId,
            'type' => StockMovementType::TransferIn,
            'quantity' => $item->quantity,
            'quantity_before' => $toBefore,
            'quantity_after' => $toAfter,
            'reason' => null,
            'user_id' => $executor->id,
            'stock_transfer_item_id' => $item->id,
        ]);
    }
}
