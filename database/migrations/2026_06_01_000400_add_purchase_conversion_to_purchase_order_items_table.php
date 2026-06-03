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
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignId('purchase_unit_id')->nullable()->after('unit_id')->constrained('units')->nullOnDelete();
            $table->decimal('purchase_qty', 20, 4)->nullable()->after('purchase_unit_id');
            $table->decimal('conversion_qty', 20, 4)->default(1)->after('purchase_qty');
            $table->decimal('purchase_unit_cost', 20, 4)->nullable()->after('unit_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_unit_id');
            $table->dropColumn(['purchase_qty', 'conversion_qty', 'purchase_unit_cost']);
        });
    }
};
