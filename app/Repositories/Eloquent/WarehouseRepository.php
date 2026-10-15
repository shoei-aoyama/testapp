<?php

namespace App\Repositories\Eloquent;

use App\Models\Warehouse;
use App\Repositories\Contracts\WarehouseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class WarehouseRepository implements WarehouseRepositoryInterface
{
  public function find(int $id): ?Warehouse
  {
    return Warehouse::find($id);
  }

  public function findManyByIds(array $ids): Collection
  {
    return Warehouse::query()
      ->whereIn('id', $ids)
      ->get()
      ->keyBy('id');
  }

  public function create(array $attributes): Warehouse
  {
    return Warehouse::create($attributes);
  }

  public function update(Warehouse $warehouse, array $attributes): void
  {
    $warehouse->update($attributes);
  }

  public function delete(Warehouse $warehouse): void
  {
    $warehouse->delete();
  }
}