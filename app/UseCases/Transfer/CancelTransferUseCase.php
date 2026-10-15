<?php

namespace App\UseCases\Transfer;

use App\Enums\StockTransferStatus;
use App\Exceptions\Business\InvalidStateTransitionException;
use App\Models\StockTransfer;
use App\Models\User;
use App\Repositories\Contracts\StockTransferRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * 倉庫間移動のキャンセル。在庫は動かさない。
 * 商品・倉庫が無効になっていても、キャンセルはできる。
 */
class CancelTransferUseCase
{
    public function __construct(
        private readonly StockTransferRepositoryInterface $transfers,
    ) {}

    /**
     * @param  User  $executor  実行者
     *
     * @throws ModelNotFoundException 移動が存在しない
     * @throws InvalidStateTransitionException pending でない
     */
    public function execute(User $executor, int $transferId): StockTransfer
    {
        return DB::transaction(function () use ($executor, $transferId) {
            $transfer = $this->transfers->findForUpdate($transferId);

            if ($transfer === null) {
                throw (new ModelNotFoundException)->setModel(StockTransfer::class, [$transferId]);
            }

            if ($transfer->status !== StockTransferStatus::Pending) {
                throw new InvalidStateTransitionException($transfer->status, StockTransferStatus::Cancelled);
            }

            $this->transfers->markCancelled($transfer, $executor->id, now());

            return $transfer;
        }, 3);
    }
}
