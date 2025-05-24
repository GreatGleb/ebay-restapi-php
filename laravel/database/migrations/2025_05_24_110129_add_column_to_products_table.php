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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('supplier_price_net', 10, 2)->nullable()->comment('Supplier price without VAT');
            $table->decimal('supplier_price_gross', 10, 2)->nullable()->comment('Supplier price with VAT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('supplier_price_net');
            $table->dropColumn('supplier_price_gross');
        });
    }
};
