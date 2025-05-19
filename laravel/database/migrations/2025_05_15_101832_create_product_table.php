<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Triggers\UpdatedAtTrigger;

return new class extends Migration
{
    public $tableName = 'products';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->id()->comment('#');

            // Product identification
            $table->string('reference', 255)->nullable()->comment('Reference');
            $table->string('tecdoc_number', 255)->nullable()->comment('TecDoc number');

            // Pricing
            $table->decimal('retail_price_net', 10, 2)->nullable()->comment('Retail price without VAT');
            $table->decimal('retail_price_gross', 10, 2)->nullable()->comment('Retail price with VAT');

            // Availability
            $table->unsignedInteger('stock_quantity_pl')->default(0)->comment('Quantity PL');
            $table->unsignedInteger('stock_quantity_pruszkow')->default(0)->comment('Quantity Pruszkow');

            // Descriptions
            $table->string('name_original_pl', 255)->nullable()->comment('Name original pl');
            $table->text('internal_description')->nullable()->comment('Internal description');

            // Category information
            $table->string('ru_category_from_ebay_de', 255)->nullable()->comment('Category eBay.de Russian');
            $table->string('category_id_ebay_de', 50)->nullable()->comment('Category id eBay.de');

            // Product details
            $table->string('installation_position_en', 255)->nullable()->comment('Installation position English');
            $table->text('specifics_ru')->nullable()->comment('Specifics Russian');
            $table->text('specifics_en')->nullable()->comment('Specifics English');
            $table->text('specifics_de')->nullable()->comment('Specifics German');
            $table->string('product_type_ru', 255)->nullable()->comment('Product type Russian');
            $table->string('product_type_en', 255)->nullable()->comment('Product type English');
            $table->string('product_type_de', 255)->nullable()->comment('Product type German');
            $table->string('part_of_ebay_de_name_product_type', 255)->nullable()->comment('Part of eBay.de name - product type');
            $table->string('part_of_ebay_name_for_cars', 255)->nullable()->comment('Part of eBay name - for cars');

            // eBay information
            $table->string('ebay_name_ru', 255)->nullable()->comment('eBay name Russian');
            $table->string('ebay_name_en', 255)->nullable()->comment('eBay name English');
            $table->string('ebay_name_de', 255)->nullable()->comment('eBay name German');
            $table->text('description_to_ebay_de')->nullable()->comment('Description to eBay.de');
            $table->text('specifics_to_ebay_de')->nullable()->comment('Specifics to eBay.de');

            // Product features
            $table->boolean('has_hologram')->default(false)->comment('Has hologram');
            $table->boolean('no_photo')->default(false)->comment('No photo');

            // Supplier information
            $table->string('supplier', 255)->nullable()->comment('Supplier');
            $table->string('producer_brand', 255)->nullable()->comment('Producer brand');

            // Product codes and dimensions
            $table->string('ean', 50)->nullable()->comment('EAN');
            $table->integer('weight_gram')->nullable()->comment('weight gram');
            $table->integer('box_length_cm')->nullable()->comment('box length cm');
            $table->integer('box_width_cm')->nullable()->comment('box width cm');
            $table->integer('box_height_cm')->nullable()->comment('box height cm');

            // Additional info
            $table->text('link')->nullable()->comment('Link');
            $table->text('comment')->nullable()->comment('Comment');

            // eBay status
            $table->boolean('published_to_ebay_de')->default(false)->comment('Published to eBay.de?');
            $table->dateTime('last_update_to_ebay_de')->nullable()->comment('Last update to eBay.de');
            $table->unsignedInteger('order_creation_to_ebay_de')->nullable()->comment('Order creation to eBay.de');

            // Sales
            $table->integer('sold_in_general')->default(0)->comment('Sold in general');

            // Indexes
            $table->index('reference');
            $table->index('tecdoc_number');
            $table->index('supplier');
            $table->index('producer_brand');

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
