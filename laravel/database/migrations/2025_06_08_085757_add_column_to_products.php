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
            $table->string('internal_reference', 255)->nullable()->comment('Internal reference');
        });

        DB::unprepared('
            CREATE OR REPLACE FUNCTION set_internal_reference_column()
            RETURNS TRIGGER AS $$
            BEGIN
                UPDATE products
                SET internal_reference = CONCAT(\'crtx\', NEW.id)
                WHERE id = NEW.id;
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER set_internal_reference_column_after
            AFTER INSERT ON products
            FOR EACH ROW
            EXECUTE FUNCTION set_internal_reference_column();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('
            DROP TRIGGER IF EXISTS set_internal_reference_column_after ON products;
            DROP FUNCTION IF EXISTS set_internal_reference_column();
        ');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('internal_reference');
        });
    }
};
