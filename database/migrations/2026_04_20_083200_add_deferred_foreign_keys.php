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
        Schema::table('production_recipe_items', function (Blueprint $table) {
            $table
                ->foreign('production_recipe_id')
                ->references('id')
                ->on('production_recipes')
                ->cascadeOnDelete();
        });

        Schema::table('transfer_items', function (Blueprint $table) {
            $table
                ->foreign('transfer_id')
                ->references('id')
                ->on('transfers')
                ->cascadeOnDelete();
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table
                ->foreign('last_movement_item_id')
                ->references('id')
                ->on('stock_movement_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_recipe_items', function (Blueprint $table) {
            $table->dropForeign(['production_recipe_id']);
        });

        Schema::table('transfer_items', function (Blueprint $table) {
            $table->dropForeign(['transfer_id']);
        });

        Schema::table('stock_balances', function (Blueprint $table) {
            $table->dropForeign(['last_movement_item_id']);
        });
    }
};
