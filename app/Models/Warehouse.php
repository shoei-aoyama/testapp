<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Warehouse extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'postal_code',
        'address',
        'phone',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * この倉庫を移動元とする倉庫間移動。
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'from_warehouse_id');
    }

    /**
     * この倉庫を移動先とする倉庫間移動。
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(StockTransfer::class, 'to_warehouse_id');
    }
}
