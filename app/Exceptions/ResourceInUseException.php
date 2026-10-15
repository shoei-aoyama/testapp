<?php

namespace App\Exceptions\Business;

/**
 * 削除条件を満たさない場合
 * （履歴・在庫・倉庫間移動・参照があるため、物理削除できない）。
 */
class ResourceInUseException extends BusinessException
{
    /**
     * @param  string  $resource  対象の種類（例: 'product', 'warehouse', 'category'）
     * @param  int  $id  対象のID
     * @param  string  $reason  削除できない理由
     */
    public function __construct(
        private readonly string $resource,
        private readonly int $id,
        private readonly string $reason,
    ) {
        parent::__construct(sprintf('削除できません（%s ID: %d）: %s', $resource, $id, $reason));
    }

    public function resource(): string
    {
        return $this->resource;
    }

    public function resourceId(): int
    {
        return $this->id;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function errorCode(): string
    {
        return 'resource_in_use';
    }
}
