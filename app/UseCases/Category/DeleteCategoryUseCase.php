<?php

namespace App\UseCases\Category;

use App\Exceptions\Business\ResourceInUseException;
use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * カテゴリの物理削除。1つでも商品から参照されていれば削除できない（無効化を使う）。
 *
 * 確認の直後に別の処理が商品を追加した場合は、DBの外部キー制約（RESTRICT）が削除を止める。
 */
class DeleteCategoryUseCase
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * @throws ModelNotFoundException カテゴリが存在しない
     * @throws ResourceInUseException 商品から参照されている
     */
    public function execute(int $categoryId): void
    {
        $category = $this->categories->find($categoryId);

        if ($category === null) {
            throw (new ModelNotFoundException)->setModel(Category::class, [$categoryId]);
        }

        if ($this->products->existsByCategory($categoryId)) {
            throw new ResourceInUseException('category', $categoryId, '商品から参照されています。');
        }

        $this->categories->delete($category);
    }
}
