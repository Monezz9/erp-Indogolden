<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_in_processes', function (Blueprint $table) {
            $table->id();
            $table->string('process_number')->unique();
            $table->date('process_date');
            $table->string('process_type', 20)->default('internal');
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('input_item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('output_item_id')->constrained('items')->restrictOnDelete();
            $table->foreignId('input_unit_id')->constrained('units')->restrictOnDelete();
            $table->foreignId('output_unit_id')->constrained('units')->restrictOnDelete();
            $table->decimal('input_qty', 20, 4);
            $table->decimal('standard_conversion_per_unit', 20, 4)->default(0);
            $table->decimal('expected_output_qty', 20, 4)->default(0);
            $table->decimal('actual_output_qty', 20, 4);
            $table->decimal('variance_qty', 20, 4)->default(0);
            $table->decimal('overhead_cost', 20, 4)->default(0);
            $table->decimal('input_unit_cost', 20, 4)->default(0);
            $table->decimal('total_input_cost', 20, 4)->default(0);
            $table->decimal('output_unit_cost', 20, 4)->default(0);
            $table->decimal('total_output_cost', 20, 4)->default(0);
            $table->string('vendor_name')->nullable();
            $table->string('status', 20)->default('posted');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->index(['process_type', 'process_date']);
            $table->index(['input_item_id', 'output_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_in_processes');
    }
};
