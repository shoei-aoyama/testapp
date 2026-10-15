<?php

namespace App\UseCases\Product;

use App\Exceptions\Business\InactiveResourceException;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * 商品の作成。カテゴリは、存在し、有効なものだけ指定できる。
 *
 * 商品コードの重複などの形式チェックは FormRequest とDBのUNIQUE制約が担当する。
 */
class CreateProductUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @param  array{code: string, name: string, category_id: int, unit_price: int, is_active?: bool}  $attributes
     *
     * @throws ModelNotFoundException カテゴリが存在しない
     * @throws InactiveResourceException カテゴリが無効
     */
    public function execute(array $attributes): Product
    {
        $category = $this->categories->find($attributes['category_id']);

        if ($category === null) {
            throw (new ModelNotFoundException)->setModel(Category::class, [$attributes['category_id']]);
        }

        if (! $category->is_active) {
            throw new InactiveResourceException(InactiveResourceException::CATEGORY, [$category->id]);
        }

        return $this->products->create($attributes);
    }
}
