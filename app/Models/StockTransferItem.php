<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransferItem extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'quantity',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * この明細に紐づく在庫変動履歴（TRANSFER_OUT / TRANSFER_IN）。
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'stock_transfer_item_id');
    }
}
