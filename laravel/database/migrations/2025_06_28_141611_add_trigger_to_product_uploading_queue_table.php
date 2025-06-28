<?php

use Illuminate\Database\Migrations\Migration;
use Database\Triggers\UpdatedAtTrigger;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $tableName = 'product_uploading_queue';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(UpdatedAtTrigger::create($this->tableName));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(UpdatedAtTrigger::drop($this->tableName));
    }
};
