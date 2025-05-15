<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Triggers\UpdatedAtTrigger;

return new class extends Migration
{
    public $tableName = 'tecdoc_cars';
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('car_tecdoc_id');
            $table->unsignedInteger('car_type_number');
            $table->unsignedInteger('car_rmi_type_id');

            // Car identification
            $table->unsignedInteger('car_manufacturer_id');
            $table->string('car_manufacturer_name', 250);
            $table->unsignedInteger('car_model_id');
            $table->string('car_model_name', 250);

            // Car specifications
            $table->string('car_body_type', 250);
            $table->date('car_production_start')->comment('Формат: YYYY-MM-01');
            $table->date('car_production_end')->comment('Формат: YYYY-MM-01');
            $table->unsignedInteger('car_tonnage')->nullable();

            // Engine specifications
            $table->string('engine_type_name', 250);
            $table->string('engine_motor_type', 250);
            $table->string('engine_fuel_type', 250);
            $table->string('engine_fuel_injection', 250);
            $table->unsignedSmallInteger('engine_displacement_ccm');
            $table->decimal('engine_displacement_liter', 3, 1);
            $table->unsignedTinyInteger('engine_cylinders');
            $table->unsignedTinyInteger('engine_valves_per_cylinder');
            $table->unsignedSmallInteger('engine_power_hp');
            $table->unsignedSmallInteger('engine_power_kw');

            // Transmission specifications
            $table->string('transmission_drive_type', 250);
            $table->string('transmission_axis_configuration', 250)->nullable();
            $table->string('transmission_brake_system', 250)->nullable();

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
