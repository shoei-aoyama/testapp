<?php

namespace App\Models;

use App\Enums\StockTransferStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'requested_by',
        'completed_by',
        'completed_at',
        'cancelled_by',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StockTransferStatus::class,
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * 申請者。
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * 完了者。
     */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * キャンセル実行者。
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
