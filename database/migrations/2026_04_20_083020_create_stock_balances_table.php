<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->string('balance_key')->unique();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('stage_id')->constrained('item_stages')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('stock_batch_id')->nullable()->constrained('stock_batches')->nullOnDelete();
            $table->decimal('qty_on_hand', 20, 4)->default(0);
            $table->decimal('avg_cost', 20, 4)->default(0);
            $table->decimal('total_value', 20, 4)->default(0);
            $table->unsignedBigInteger('last_movement_item_id')->nullable();
            $table->dateTime('last_updated_at')->nullable();
            $table->timestamps();

            $table->index(['item_id', 'stage_id']);
            $table->index(['warehouse_id', 'branch_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_balances');
    }
};
