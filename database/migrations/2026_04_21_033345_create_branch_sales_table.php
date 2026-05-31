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
        Schema::create('branch_sales', function (Blueprint $table) {
            $table->id();
            $table->string('sale_number', 40)->unique();
            $table->dateTime('sale_date');
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->string('status', 20)->default('draft');
            $table->string('payment_method', 30)->default('cash');
            $table->decimal('subtotal', 20, 2)->default(0);
            $table->decimal('discount_amount', 20, 2)->default(0);
            $table->decimal('tax_amount', 20, 2)->default(0);
            $table->decimal('total_amount', 20, 2)->default(0);
            $table->decimal('cogs_amount', 20, 4)->default(0);
            $table->decimal('gross_profit', 20, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('posted_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'sale_date']);
            $table->index(['status', 'sale_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_sales');
    }
};
