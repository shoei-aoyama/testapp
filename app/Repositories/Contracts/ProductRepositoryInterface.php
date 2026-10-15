<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

/**
 * 商品（products）のデータアクセス。
 */
interface ProductRepositoryInterface
{
  /**
   * 商品を1件取得する。存在しなければ null。
   */
  public function find(int $id): ?Product;

  /**
   * 指定したIDの商品をまとめて取得する。存在しないIDは含まれない。
   * 戻り値は商品IDをキーにしたコレクション。
   *
   * @param  list<int>  $ids
   * @return Collection<int, Product>
   */
  public function findManyByIds(array $ids): Collection;

  /**
   * 商品を作成する。
   *
   * @param  array{code: string, name: string, category_id: int, unit_price: int, is_active?: bool}  $attributes
   */
  public function create(array $attributes): Product;

  /**
   * 商品を更新する（渡した項目のみ）。
   *
   * @param  array<string, mixed>  $attributes
   */
  public function update(Product $product, array $attributes): void;

  /**
   * 商品を物理削除する。削除できる条件の確認は、呼び出し側（UseCase）で行う。
   */
  public function delete(Product $product): void;

  /**
   * カテゴリが、いずれかの商品から参照されているか（カテゴリの物理削除の判定に使う）。
   */
  public function existsByCategory(int $categoryId): bool;
}