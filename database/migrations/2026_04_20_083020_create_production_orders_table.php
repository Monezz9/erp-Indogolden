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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('production_recipe_id')->nullable()->constrained('production_recipes')->nullOnDelete();
            $table->string('status', 20)->default('draft');
            $table->date('planned_date')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('output_item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('output_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->decimal('target_qty', 20, 4);
            $table->decimal('actual_qty', 20, 4)->default(0);
            $table->decimal('shrinkage_qty', 20, 4)->default(0);
            $table->decimal('total_input_cost', 20, 4)->default(0);
            $table->decimal('total_output_cost', 20, 4)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'planned_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');
    }
};
