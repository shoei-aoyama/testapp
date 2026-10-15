<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('stock_transfer_items', function (Blueprint $table) {
      $table->id();
      $table->foreignId('stock_transfer_id')->constrained('stock_transfers')->restrictOnDelete();
      $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
      $table->unsignedInteger('quantity');
      $table->timestamps();

      // 1回の移動の中で同一商品を重複して持てないようにする
      $table->unique(['stock_transfer_id', 'product_id']);

      // stock_movements の複合外部キー（明細ID + 商品ID）の参照先にするためのユニーク制約
      $table->unique(['id', 'product_id'], 'stock_transfer_items_id_product_id_unique');
    });

    DB::statement('ALTER TABLE stock_transfer_items ADD CONSTRAINT stock_transfer_items_quantity_check CHECK (quantity >= 1)');
  }

  public function down(): void
  {
    Schema::dropIfExists('stock_transfer_items');
  }
};