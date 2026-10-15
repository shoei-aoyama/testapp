<?php

namespace App\Enums;

enum StockMovementType: string
{
    case In = 'IN';
    case Out = 'OUT';
    case AdjustmentIn = 'ADJUSTMENT_IN';
    case AdjustmentOut = 'ADJUSTMENT_OUT';
    case TransferIn = 'TRANSFER_IN';
    case TransferOut = 'TRANSFER_OUT';

    /**
     * 在庫が増える種別かどうか。減る種別は false になる。
     */
    public function isIncrease(): bool
    {
        return match ($this) {
            self::In, self::AdjustmentIn, self::TransferIn => true,
            self::Out, self::AdjustmentOut, self::TransferOut => false,
        };
    }
}
