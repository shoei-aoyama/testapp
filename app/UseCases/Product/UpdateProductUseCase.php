<?php

namespace App\UseCases\Product;

use App\Exceptions\Business\InactiveResourceException;
use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * 商品の更新。
 *
 * カテゴリを別のものに変えるときだけ、新しいカテゴリが有効であることを確認する。
 * カテゴリを変えない場合は、そのカテゴリが無効になっていても更新できる。
 */
class UpdateProductUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes  変更する項目（code, name, category_id, unit_price, is_active のうち渡されたもの）
     *
     * @throws ModelNotFoundException 商品・カテゴリが存在しない
     * @throws InactiveResourceException 変更先のカテゴリが無効
     */
    public function execute(int $productId, array $attributes): Product
    {
        $product = $this->products->find($productId);

        if ($product === null) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);
        }

        if (isset($attributes['category_id']) && (int) $attributes['category_id'] !== $product->category_id) {
            $category = $this->categories->find((int) $attributes['category_id']);

            if ($category === null) {
                throw (new ModelNotFoundException)->setModel(Category::class, [$attributes['category_id']]);
            }

            if (! $category->is_active) {
                throw new InactiveResourceException(InactiveResourceException::CATEGORY, [$category->id]);
            }
        }

        $this->products->update($product, $attributes);

        return $product;
    }
}
