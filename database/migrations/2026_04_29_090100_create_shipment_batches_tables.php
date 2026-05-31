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
        Schema::create('shipment_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('shipment_number', 50)->unique();
            $table->date('delivery_date')->index();
            $table->string('status', 30)->default('draft')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('shipment_batch_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shipment_batch_id')->constrained('shipment_batches')->cascadeOnDelete();
            $table->foreignId('request_id')->constrained('branch_requests')->cascadeOnDelete();
            $table->foreignId('request_item_id')->constrained('branch_request_items')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('qty_to_ship', 14, 3)->default(0);
            $table->decimal('qty_packed', 14, 3)->default(0);
            $table->decimal('qty_shipped', 14, 3)->default(0);
            $table->string('status', 30)->default('draft')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_batch_items');
        Schema::dropIfExists('shipment_batches');
    }
};
