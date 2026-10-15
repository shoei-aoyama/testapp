<?php

namespace App\Repositories\Contracts;

use App\Models\Category;

/**
 * カテゴリ（categories）のデータアクセス。
 */
interface CategoryRepositoryInterface
{
    /**
     * カテゴリを1件取得する。存在しなければ null。
     */
    public function find(int $id): ?Category;

    /**
     * カテゴリを作成する。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Category;

    /**
     * カテゴリを更新する（渡した項目のみ。有効・無効の切り替えも含む）。
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Category $category, array $attributes): void;

    /**
     * カテゴリを物理削除する。削除できる条件の確認は、呼び出し側（UseCase）で行う。
     */
    public function delete(Category $category): void;
}
