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
        Schema::create('receipt_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->nullable();
            $table->text('store_address')->nullable();
            $table->string('store_phone', 50)->nullable();
            $table->text('footer_text')->nullable();
            $table->enum('paper_size', ['58mm', '80mm', 'A4'])->default('80mm');
            $table->boolean('show_logo')->default(false);
            $table->string('logo_path')->nullable();
            $table->boolean('show_qris')->default(false);
            $table->string('qris_image_path')->nullable();
            $table->boolean('show_discount')->default(true);
            $table->boolean('show_tax')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_settings');
    }
};
