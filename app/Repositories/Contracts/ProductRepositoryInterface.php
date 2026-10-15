<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * 商品（products）のデータアクセス。
 * 登録・更新・削除のメソッドは、商品管理のUseCaseを実装するときに追加する。
 */
interface ProductRepositoryInterface
{
    /**
     * 指定したIDの商品をまとめて取得する。存在しないIDは含まれない。
     * 戻り値は商品IDをキーにしたコレクション。
     *
     * @param  list<int>  $ids
     * @return Collection<int, Product>
     */
    public function findManyByIds(array $ids): Collection;
}
