<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Triggers\UpdatedAtTrigger;

return new class extends Migration
{
    public $tableName = 'product_tecdoc_data';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained();

            // Supplier and manufacturer info
            $table->unsignedInteger('data_supplier_id');
            $table->string('article_number', 250);
            $table->unsignedInteger('mfr_id');
            $table->string('mfr_name', 250);

            // Article content
            $table->json('misc')->nullable();
            $table->json('article_text')->nullable();

            // Identification numbers
            $table->json('gtins')->nullable();
            $table->json('trade_numbers')->nullable();

            // Article relationships
            $table->json('replaces_articles')->nullable();
            $table->json('replaced_by_articles')->nullable();
            $table->json('generic_articles')->nullable();

            // Classification and criteria
            $table->json('article_criteria')->nullable();

            // Product linkages
            $table->json('linkages')->nullable();
            $table->unsignedInteger('total_linkages')->default(0);

            // Media and documents
            $table->json('pdfs')->nullable();

            // Comparable data
            $table->json('comparable_numbers')->nullable();
            $table->json('search_query_matches')->nullable();

            // External references
            $table->json('links')->nullable();

            // Pricing information
            $table->json('prices')->nullable();

            // Indexes
            $table->index('data_supplier_id');

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
