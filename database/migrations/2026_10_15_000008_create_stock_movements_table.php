<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('stock_movements', function (Blueprint $table) {
      $table->id();
      $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
      $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
      $table->string('type', 20);
      // 数量は常に正数。増減の向きは type で判断する
      $table->unsignedInteger('quantity');
      $table->unsignedInteger('quantity_before');
      $table->unsignedInteger('quantity_after');
      $table->string('reason', 50)->nullable();
      $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

      // 倉庫間移動由来の履歴のみ、対応する移動明細に紐づく（それ以外はNULL）
      $table->unsignedBigInteger('stock_transfer_item_id')->nullable();

      // 履歴は追記のみのため updated_at は持たない。created_at が実行日時
      $table->timestamp('created_at')->useCurrent();

      // 複合外部キー: 履歴の商品と、紐づく移動明細の商品が一致していること
      // （stock_transfer_item_id がNULLの履歴ではチェックされない）
      $table->foreign(['stock_transfer_item_id', 'product_id'], 'stock_movements_item_product_foreign')
        ->references(['id', 'product_id'])
        ->on('stock_transfer_items')
        ->restrictOnDelete();

      // 1つの移動明細につき、TRANSFER_OUT と TRANSFER_IN は各1件まで
      $table->unique(['stock_transfer_item_id', 'type'], 'stock_movements_item_type_unique');

      $table->index('created_at');
      $table->index(['product_id', 'created_at']);
      $table->index(['warehouse_id', 'created_at']);
      $table->index('type');
    });

    DB::statement('ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_quantity_check CHECK (quantity >= 1)');

    DB::statement("ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_type_check CHECK (type IN ('IN', 'OUT', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT', 'TRANSFER_IN', 'TRANSFER_OUT'))");

    // 変更前後の数量が、種別の増減方向と一致していること
    DB::statement(<<<'SQL'
ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_balance_check CHECK (
    (type IN ('IN', 'ADJUSTMENT_IN', 'TRANSFER_IN')
        AND quantity_after = quantity_before + quantity)
    OR
    (type IN ('OUT', 'ADJUSTMENT_OUT', 'TRANSFER_OUT')
        AND quantity_after + quantity = quantity_before)
)
SQL);

    // 倉庫間移動の種別のときだけ、関連する移動明細が必須
    DB::statement(<<<'SQL'
ALTER TABLE stock_movements ADD CONSTRAINT stock_movements_transfer_check CHECK (
    (type IN ('TRANSFER_IN', 'TRANSFER_OUT') AND stock_transfer_item_id IS NOT NULL)
    OR
    (type IN ('IN', 'OUT', 'ADJUSTMENT_IN', 'ADJUSTMENT_OUT') AND stock_transfer_item_id IS NULL)
)
SQL);
  }

  public function down(): void
  {
    Schema::dropIfExists('stock_movements');
  }
};