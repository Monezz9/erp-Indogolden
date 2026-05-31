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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 60)->unique();
            $table->string('name');
            $table->foreignId('item_category_id')->constrained('item_categories')->restrictOnDelete();
            $table->foreignId('default_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('default_stage_id')->nullable()->constrained('item_stages')->nullOnDelete();
            $table->string('item_type', 30)->default('material');
            $table->boolean('requires_production')->default(false);
            $table->boolean('is_perishable')->default(false);
            $table->decimal('minimum_stock', 20, 4)->default(0);
            $table->decimal('latest_weighted_avg_cost', 20, 4)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['item_category_id', 'item_type']);
            $table->index(['is_active', 'minimum_stock']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
