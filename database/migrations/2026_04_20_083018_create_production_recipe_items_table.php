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
        Schema::create('production_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_recipe_id');
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('stage_id')->nullable()->constrained('item_stages')->nullOnDelete();
            $table->decimal('qty', 20, 4);
            $table->boolean('is_optional')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['production_recipe_id', 'item_id', 'unit_id'], 'recipe_item_unit_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_recipe_items');
    }
};
