<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Triggers\UpdatedAtTrigger;

return new class extends Migration
{
    public $tableName = 'supplier_brands';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('tecdoc_id');
            $table->string('brand_name', 255)->comment('Названия бренда товаров');

            // Timestamps
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));

            $table->index('brand_name');
        });

        DB::unprepared(UpdatedAtTrigger::create($this->tableName));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(UpdatedAtTrigger::drop($this->tableName));
        Schema::dropIfExists($this->tableName);
    }
};
