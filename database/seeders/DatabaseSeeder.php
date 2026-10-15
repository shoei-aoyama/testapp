<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  use WithoutModelEvents;

  /**
   * Seed the application's database.
   *
   * 固定データ（毎回同じ内容）を先に入れ、そのあとでランダムなダミーデータを入れる。
   * 固定データだけが必要なときは、--class で個別に実行する。
   */
  public function run(): void
  {
    $this->call([
      // 固定データ
      CategorySeeder::class,
      WarehouseSeeder::class,
      UserSeeder::class,
      ProductSeeder::class,

      // ランダムなダミーデータ（固定データに依存する）
      DummyDataSeeder::class,
    ]);
  }
}