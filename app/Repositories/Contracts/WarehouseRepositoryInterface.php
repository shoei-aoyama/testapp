<?php

namespace App\Repositories\Contracts;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

/**
 * 倉庫（warehouses）のデータアクセス。
 */
interface WarehouseRepositoryInterface
{
  /**
   * 倉庫を1件取得する。存在しなければ null。
   */
  public function find(int $id): ?Warehouse;

  /**
   * 指定したIDの倉庫をまとめて取得する。存在しないIDは含まれない。
   * 戻り値は倉庫IDをキーにしたコレクション。
   *
   * @param  list<int>  $ids
   * @return Collection<int, Warehouse>
   */
  public function findManyByIds(array $ids): Collection;

  /**
   * 倉庫を作成する。
   *
   * @param  array<string, mixed>  $attributes
   */
  public function create(array $attributes): Warehouse;

  /**
   * 倉庫を更新する（渡した項目のみ。有効・無効の切り替えも含む）。
   *
   * @param  array<string, mixed>  $attributes
   */
  public function update(Warehouse $warehouse, array $attributes): void;

  /**
   * 倉庫を物理削除する。削除できる条件の確認は、呼び出し側（UseCase）で行う。
   */
  public function delete(Warehouse $warehouse): void;
}