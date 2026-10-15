<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * 固定データ: カテゴリ（商品 / 資材 / 備品）。
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['商品', '資材', '備品'] as $name) {
            Category::updateOrCreate(['name' => $name], ['is_active' => true]);
        }
    }
}
