<?php

namespace App\Exceptions\Business;

/**
 * 無効な商品・倉庫・カテゴリを使おうとした場合。
 */
class InactiveResourceException extends BusinessException
{
    public const PRODUCT = 'product';

    public const WAREHOUSE = 'warehouse';

    public const CATEGORY = 'category';

    private const LABELS = [
        self::PRODUCT => '商品',
        self::WAREHOUSE => '倉庫',
        self::CATEGORY => 'カテゴリ',
    ];

    /**
     * @param  string  $resource  PRODUCT / WAREHOUSE / CATEGORY のいずれか
     * @param  list<int>  $ids  無効だったもののID
     */
    public function __construct(
        private readonly string $resource,
        private readonly array $ids,
    ) {
        parent::__construct(sprintf(
            '無効な%sは使用できません（ID: %s）。',
            self::LABELS[$resource] ?? $resource,
            implode(', ', $ids),
        ));
    }

    public function resource(): string
    {
        return $this->resource;
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return $this->ids;
    }

    public function errorCode(): string
    {
        return 'inactive_resource';
    }
}
