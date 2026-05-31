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
        Schema::create('hpp_calculations', function (Blueprint $table): void {
            $table->id();
            $table->string('calc_number', 50)->unique();
            $table->date('calc_date')->index();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('stage', 40)->default('raw_material')->index();
            $table->string('product_name', 150)->nullable();
            $table->decimal('total_raw_value', 16, 2)->default(0);
            $table->decimal('total_clean_value', 16, 2)->default(0);
            $table->decimal('total_production_cost', 16, 2)->default(0);
            $table->decimal('hpp_per_unit', 16, 4)->default(0);
            $table->decimal('selling_price', 16, 2)->default(0);
            $table->decimal('profit', 16, 2)->default(0);
            $table->decimal('margin_percent', 9, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('hpp_calculation_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hpp_calculation_id')->constrained('hpp_calculations')->cascadeOnDelete();
            $table->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->string('line_type', 40)->default('raw_material');
            $table->string('item_name', 150);
            $table->decimal('purchase_price', 16, 2)->default(0);
            $table->decimal('stock_awal', 14, 3)->default(0);
            $table->decimal('stock_masuk', 14, 3)->default(0);
            $table->decimal('stock_keluar', 14, 3)->default(0);
            $table->decimal('hasil_bersih', 14, 3)->default(0);
            $table->decimal('additional_cost', 16, 2)->default(0);
            $table->decimal('total_value', 16, 2)->default(0);
            $table->decimal('hpp_result', 16, 4)->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hpp_calculation_lines');
        Schema::dropIfExists('hpp_calculations');
    }
};
