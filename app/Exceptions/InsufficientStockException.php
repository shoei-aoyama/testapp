<?php

namespace App\Exceptions\Business;

/**
 * 出庫・倉庫間移動の完了で、在庫が足りない場合。
 * 不足した商品は、1件ずつではなくまとめて報告する。
 */
class InsufficientStockException extends BusinessException
{
    /**
     * @param  list<array{product_id: int, warehouse_id: int, requested: int, available: int}>  $shortages
     */
    public function __construct(private readonly array $shortages)
    {
        parent::__construct('在庫が不足しています。');
    }

    /**
     * @return list<array{product_id: int, warehouse_id: int, requested: int, available: int}>
     */
    public function shortages(): array
    {
        return $this->shortages;
    }

    public function errorCode(): string
    {
        return 'insufficient_stock';
    }
}
