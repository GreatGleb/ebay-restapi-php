<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Triggers\UpdatedAtTrigger;

return new class extends Migration
{
    public $tableName = 'product_photos';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained();
            $table->text('original_photo_url')->comment('Оригинальное фото');
            $table->text('cortexparts_photo_url')->nullable()->comment('Фото с нашим лого');

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
