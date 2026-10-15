<?php

namespace App\Models;

use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    use HasFactory;

    /**
     * 履歴は追記のみで更新しないため、updated_at は持たない。
     * created_at が実行日時になる。
     */
    public const UPDATED_AT = null;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'type',
        'quantity',
        'quantity_before',
        'quantity_after',
        'reason',
        'user_id',
        'stock_transfer_item_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
            'quantity_before' => 'integer',
            'quantity_after' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * 実行者。
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 倉庫間移動由来の場合の、対応する移動明細。
     */
    public function transferItem(): BelongsTo
    {
        return $this->belongsTo(StockTransferItem::class, 'stock_transfer_item_id');
    }
}
