<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Triggers\UpdatedAtTrigger;

return new class extends Migration
{
    public $tableName = 'product_oe_codes';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained();

            $table->string('number', 250);
            $table->unsignedInteger('car_manufacturer_id')->nullable();
            $table->string('car_manufacturer_name', 250)->nullable();

            // Timestamps
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP'));
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
