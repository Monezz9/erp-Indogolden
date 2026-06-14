<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->foreignId('supplier_id')->nullable()->after('purchase_order_id')->constrained('suppliers')->nullOnDelete();
            $table->string('invoice_number', 100)->nullable()->after('receipt_date');
            $table->decimal('subtotal', 20, 4)->default(0)->after('status');
            $table->decimal('grand_total', 20, 4)->default(0)->after('subtotal');
        });

        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->dropForeign(['purchase_order_id']);
        });

        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->foreignId('purchase_order_id')->nullable()->change();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->dropForeign(['purchase_order_id']);
        });

        Schema::table('goods_receipts', function (Blueprint $table): void {
            $table->foreignId('purchase_order_id')->nullable(false)->change();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->cascadeOnDelete();
            $table->dropConstrainedForeignId('supplier_id');
            $table->dropColumn(['invoice_number', 'subtotal', 'grand_total']);
        });
    }
};
