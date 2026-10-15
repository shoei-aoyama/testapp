<?php

namespace App\Repositories\Contracts;

use App\Models\StockTransfer;
use Carbon\CarbonInterface;

/**
 * 倉庫間移動（stock_transfers / stock_transfer_items）のデータアクセス。
 *
 * 名前が ...ForUpdate で終わるメソッドは、必ずトランザクション内で使うこと。
 * トランザクション外で呼ぶとロックがすぐ解放され、ロックの意味がなくなる。
 */
interface StockTransferRepositoryInterface
{
    /**
     * ヘッダ（pending）と明細を作成して、明細を読み込んだ状態で返す。
     *
     * @param  array{from_warehouse_id: int, to_warehouse_id: int, requested_by: int}  $header
     * @param  list<array{product_id: int, quantity: int}>  $items
     */
    public function create(array $header, array $items): StockTransfer;

    /**
     * ヘッダの1行をロックして取得する。存在しなければ null。
     *
     * トランザクション内で使うこと。
     */
    public function findForUpdate(int $id): ?StockTransfer;

    /**
     * 明細（商品つき）と、移動元・移動先の倉庫を読み込んで返す。
     */
    public function loadDetails(StockTransfer $transfer): StockTransfer;

    /**
     * completed にして、完了者・完了日時を記録する。
     */
    public function markCompleted(StockTransfer $transfer, int $userId, CarbonInterface $at): void;

    /**
     * cancelled にして、キャンセル実行者・キャンセル日時を記録する。
     */
    public function markCancelled(StockTransfer $transfer, int $userId, CarbonInterface $at): void;

    /**
     * 商品が、いずれかの移動の明細に使われているか（商品の物理削除の判定に使う）。
     */
    public function existsByProduct(int $productId): bool;

    /**
     * 倉庫が、いずれかの移動の移動元・移動先に使われているか（倉庫の物理削除の判定に使う）。
     */
    public function existsByWarehouse(int $warehouseId): bool;
}
