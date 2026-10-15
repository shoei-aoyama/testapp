<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    public function findManyByIds(array $ids): Collection
    {
        return Product::query()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }
}
