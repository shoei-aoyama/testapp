<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('stock_transfers', function (Blueprint $table) {
      $table->id();
      $table->foreignId('from_warehouse_id')->constrained('warehouses')->restrictOnDelete();
      $table->foreignId('to_warehouse_id')->constrained('warehouses')->restrictOnDelete();
      $table->string('status', 20)->default('pending');

      // 申請者。申請日時は created_at が担う
      $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();

      $table->foreignId('completed_by')->nullable()->constrained('users')->restrictOnDelete();
      $table->timestamp('completed_at')->nullable();

      $table->foreignId('cancelled_by')->nullable()->constrained('users')->restrictOnDelete();
      $table->timestamp('cancelled_at')->nullable();

      $table->timestamps();

      $table->index('status');
    });

    DB::statement('ALTER TABLE stock_transfers ADD CONSTRAINT stock_transfers_warehouse_check CHECK (from_warehouse_id <> to_warehouse_id)');

    DB::statement("ALTER TABLE stock_transfers ADD CONSTRAINT stock_transfers_status_check CHECK (status IN ('pending', 'completed', 'cancelled'))");

    DB::statement(<<<'SQL'
ALTER TABLE stock_transfers ADD CONSTRAINT stock_transfers_state_check CHECK (
    (status = 'pending'
        AND completed_by IS NULL AND completed_at IS NULL
        AND cancelled_by IS NULL AND cancelled_at IS NULL)
    OR
    (status = 'completed'
        AND completed_by IS NOT NULL AND completed_at IS NOT NULL
        AND cancelled_by IS NULL AND cancelled_at IS NULL)
    OR
    (status = 'cancelled'
        AND cancelled_by IS NOT NULL AND cancelled_at IS NOT NULL
        AND completed_by IS NULL AND completed_at IS NULL)
)
SQL);
  }

  public function down(): void
  {
    Schema::dropIfExists('stock_transfers');
  }
};