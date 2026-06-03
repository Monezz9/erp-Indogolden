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
        Schema::create('cleaning_processes', function (Blueprint $table) {
            $table->id();
            $table->string('process_number', 40)->unique();
            $table->date('process_date');
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('input_qty', 20, 4);
            $table->decimal('output_qty', 20, 4);
            $table->decimal('shrinkage_qty', 20, 4)->default(0);
            $table->decimal('shrinkage_percent', 8, 4)->default(0);
            $table->decimal('input_unit_cost', 20, 4)->default(0);
            $table->decimal('output_unit_cost', 20, 4)->default(0);
            $table->decimal('total_input_cost', 20, 4)->default(0);
            $table->string('status', 20)->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->timestamps();

            $table->index(['process_date', 'status']);
            $table->index(['item_id', 'warehouse_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_processes');
    }
};
