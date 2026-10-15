<?php

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function find(int $id): ?Category
    {
        return Category::find($id);
    }

    public function create(array $attributes): Category
    {
        return Category::create($attributes);
    }

    public function update(Category $category, array $attributes): void
    {
        $category->update($attributes);
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
