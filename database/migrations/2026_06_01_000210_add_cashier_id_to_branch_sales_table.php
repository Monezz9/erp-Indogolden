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
        Schema::table('branch_sales', function (Blueprint $table) {
            $table->foreignId('cashier_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('cashiers')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('branch_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cashier_id');
        });
    }
};
