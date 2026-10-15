<?php

namespace App\Repositories\Eloquent;

use App\Enums\StockTransferStatus;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use Carbon\CarbonInterface;

class StockTransferRepository implements StockTransferRepositoryInterface
{
    public function create(array $header, array $items): StockTransfer
    {
        $transfer = StockTransfer::create(array_merge($header, [
            'status' => StockTransferStatus::Pending,
        ]));

        $transfer->items()->createMany($items);

        return $transfer->load('items');
    }

    public function findForUpdate(int $id): ?StockTransfer
    {
        return StockTransfer::query()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function loadDetails(StockTransfer $transfer): StockTransfer
    {
        return $transfer->load(['items.product', 'fromWarehouse', 'toWarehouse']);
    }

    public function markCompleted(StockTransfer $transfer, int $userId, CarbonInterface $at): void
    {
        $transfer->status = StockTransferStatus::Completed;
        $transfer->completed_by = $userId;
        $transfer->completed_at = $at;
        $transfer->save();
    }

    public function markCancelled(StockTransfer $transfer, int $userId, CarbonInterface $at): void
    {
        $transfer->status = StockTransferStatus::Cancelled;
        $transfer->cancelled_by = $userId;
        $transfer->cancelled_at = $at;
        $transfer->save();
    }

    public function existsByProduct(int $productId): bool
    {
        return StockTransferItem::query()
            ->where('product_id', $productId)
            ->exists();
    }

    public function existsByWarehouse(int $warehouseId): bool
    {
        return StockTransfer::query()
            ->where('from_warehouse_id', $warehouseId)
            ->orWhere('to_warehouse_id', $warehouseId)
            ->exists();
    }
}
