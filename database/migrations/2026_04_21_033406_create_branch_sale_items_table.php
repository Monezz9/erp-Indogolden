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
        Schema::create('branch_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_sale_id')->constrained('branch_sales')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('qty', 20, 4);
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('line_total', 20, 2)->default(0);
            $table->decimal('cogs_unit', 20, 4)->default(0);
            $table->decimal('cogs_total', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_sale_id', 'item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_sale_items');
    }
};
