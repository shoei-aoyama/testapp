<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
  public function find(int $id): ?Product
  {
    return Product::find($id);
  }

  public function findManyByIds(array $ids): Collection
  {
    return Product::query()
      ->whereIn('id', $ids)
      ->get()
      ->keyBy('id');
  }

  public function create(array $attributes): Product
  {
    return Product::create($attributes);
  }

  public function update(Product $product, array $attributes): void
  {
    $product->update($attributes);
  }

  public function delete(Product $product): void
  {
    $product->delete();
  }

  public function existsByCategory(int $categoryId): bool
  {
    return Product::query()->where('category_id', $categoryId)->exists();
  }
}