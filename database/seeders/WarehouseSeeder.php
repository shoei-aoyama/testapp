<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

/**
 * 固定データ: 倉庫。
 * 有効3件（福岡・東京・大阪）と、無効1件（旧名古屋倉庫）。
 * 大阪倉庫は、郵便番号・住所・電話番号がNULLのケース。
 */
class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'code' => 'WH-FUK',
                'name' => '福岡倉庫',
                'postal_code' => '812-0000',
                'address' => '福岡県福岡市博多区テスト町1-1-1',
                'phone' => '092-000-0001',
                'is_active' => true,
            ],
            [
                'code' => 'WH-TYO',
                'name' => '東京倉庫',
                'postal_code' => '135-0000',
                'address' => '東京都江東区テスト町2-2-2',
                'phone' => '03-0000-0002',
                'is_active' => true,
            ],
            [
                'code' => 'WH-OSA',
                'name' => '大阪倉庫',
                'postal_code' => null,
                'address' => null,
                'phone' => null,
                'is_active' => true,
            ],
            [
                'code' => 'WH-NGO',
                'name' => '旧名古屋倉庫（閉鎖）',
                'postal_code' => '455-0000',
                'address' => '愛知県名古屋市港区テスト町3-3-3',
                'phone' => '052-000-0003',
                'is_active' => false,
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::updateOrCreate(['code' => $warehouse['code']], $warehouse);
        }
    }
}
