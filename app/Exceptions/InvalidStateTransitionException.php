<?php

namespace App\Exceptions\Business;

use App\Enums\StockTransferStatus;

/**
 * 倉庫間移動の状態遷移が許可されていない場合
 * （例: pending 以外の移動を完了・キャンセルしようとした）。
 */
class InvalidStateTransitionException extends BusinessException
{
    public function __construct(
        private readonly StockTransferStatus $from,
        private readonly StockTransferStatus $to,
    ) {
        parent::__construct(sprintf(
            '倉庫間移動の状態を「%s」から「%s」へは変更できません。',
            $from->value,
            $to->value,
        ));
    }

    public function from(): StockTransferStatus
    {
        return $this->from;
    }

    public function to(): StockTransferStatus
    {
        return $this->to;
    }

    public function errorCode(): string
    {
        return 'invalid_state_transition';
    }

    /**
     * 他の操作で状態がすでに変わっているため、競合を表す 409 とする。
     */
    public function status(): int
    {
        return 409;
    }
}
