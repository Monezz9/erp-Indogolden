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
        Schema::create('branch_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->date('request_date');
            $table->date('delivery_date');
            $table->string('status', 30)->default('draft')->index();
            $table->text('note_branch')->nullable();
            $table->text('note_warehouse')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('packed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });

        Schema::create('branch_request_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('request_id')->constrained('branch_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('items')->cascadeOnDelete();
            $table->string('category', 50)->nullable();
            $table->decimal('requested_qty', 14, 3)->default(0);
            $table->decimal('approved_qty', 14, 3)->default(0);
            $table->decimal('packed_qty', 14, 3)->default(0);
            $table->decimal('shipped_qty', 14, 3)->default(0);
            $table->decimal('received_qty', 14, 3)->default(0);
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->decimal('stock_available', 14, 3)->default(0);
            $table->text('branch_note')->nullable();
            $table->text('warehouse_note')->nullable();
            $table->string('item_status', 30)->default('requested')->index();
            $table->foreignId('substitute_product_id')->nullable()->constrained('items')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_request_items');
        Schema::dropIfExists('branch_requests');
    }
};
