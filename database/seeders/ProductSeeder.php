<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * 固定データ: 商品10件（3カテゴリに分散）。
 * P-009 は単価NULL、P-010 は無効商品。
 * CategorySeeder を先に実行しておくこと。
 */
class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['code' => 'P-001', 'name' => 'ミネラルウォーター 500ml', 'category' => '商品', 'unit_price' => 120, 'is_active' => true],
            ['code' => 'P-002', 'name' => '缶コーヒー 190g', 'category' => '商品', 'unit_price' => 150, 'is_active' => true],
            ['code' => 'P-003', 'name' => 'カップ麺 醤油', 'category' => '商品', 'unit_price' => 220, 'is_active' => true],
            ['code' => 'P-004', 'name' => 'チョコレート詰め合わせ', 'category' => '商品', 'unit_price' => 880, 'is_active' => true],
            ['code' => 'P-005', 'name' => '段ボール箱 Mサイズ', 'category' => '資材', 'unit_price' => 85, 'is_active' => true],
            ['code' => 'P-006', 'name' => '梱包用テープ', 'category' => '資材', 'unit_price' => 240, 'is_active' => true],
            ['code' => 'P-007', 'name' => '緩衝材ロール', 'category' => '資材', 'unit_price' => 1500, 'is_active' => true],
            ['code' => 'P-008', 'name' => 'ボールペン 黒', 'category' => '備品', 'unit_price' => 110, 'is_active' => true],
            ['code' => 'P-009', 'name' => 'ラベルシール A4', 'category' => '備品', 'unit_price' => null, 'is_active' => true],
            ['code' => 'P-010', 'name' => '作業用手袋（廃番）', 'category' => '備品', 'unit_price' => 300, 'is_active' => false],
        ];

        foreach ($products as $product) {
            $categoryId = Category::where('name', $product['category'])->value('id');

            Product::updateOrCreate(
                ['code' => $product['code']],
                [
                    'name' => $product['name'],
                    'category_id' => $categoryId,
                    'unit_price' => $product['unit_price'],
                    'is_active' => $product['is_active'],
                ]
            );
        }
    }
}
