<?php

namespace App\Repositories\Contracts;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Collection;

/**
 * 倉庫（warehouses）のデータアクセス。
 * 登録・更新・削除のメソッドは、倉庫管理のUseCaseを実装するときに追加する。
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
}
