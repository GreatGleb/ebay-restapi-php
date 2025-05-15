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
            $table->id();

            // Product identification
            $table->text('comment')->nullable()->comment('Комментарий');
            $table->text('link')->nullable()->comment('Ссылка');
            $table->string('reference', 255)->nullable()->comment('Внутренний артикул');
            $table->string('tecdoc_number', 255)->nullable()->comment('TecDoc номер');
            $table->text('specifics')->nullable()->comment('Особенности');

            // Category information
            $table->string('category', 255)->nullable()->comment('Категория');
            $table->string('category_ebay_id', 50)->nullable()->comment('ID категории на eBay.de');

            // Descriptions
            $table->text('internal_description')->nullable()->comment('Внутреннее описание');
            $table->text('name_original_pl')->nullable()->comment('Оригинальное название на польском');

            // Pricing
            $table->decimal('retail_price_net', 10, 2)->nullable()->comment('Цена без НДС');
            $table->decimal('retail_price_gross', 10, 2)->nullable()->comment('Цена с НДС');

            // Availability
            $table->boolean('availability_pl')->default(false)->comment('Наличие в PL');
            $table->boolean('availability_pruszkow')->default(false)->comment('Наличие в Mag. Oddział Pruszków');

            // Product details
            $table->string('installation_position', 255)->nullable()->comment('Позиция установки');
            $table->string('product_type', 255)->nullable()->comment('Тип продукта');

            // eBay information
            $table->string('part_of_ebay_name', 255)->nullable()->comment('Часть названия для eBay');
            $table->string('ebay_name_ru', 255)->nullable()->comment('Название eBay на русском');
            $table->string('ebay_name_en', 255)->nullable()->comment('Название eBay на английском');
            $table->string('ebay_name_de', 255)->nullable()->comment('Название eBay на немецком');
            $table->string('ebay_name_full', 255)->nullable()->comment('Полное название для ebay.de');
            $table->boolean('published_ebay_de')->default(false)->comment('Опубликовано на ebay.de');
            $table->dateTime('last_update_ebay')->nullable()->comment('Последнее обновление');

            // Product features
            $table->boolean('has_hologram')->default(false)->comment('С голограммой');
            $table->boolean('no_photo')->default(false)->comment('Без фото');
            $table->boolean('sold_general')->default(false)->comment('Продан в общем');

            // Supplier information
            $table->string('supplier', 255)->nullable()->comment('Поставщик');
            $table->string('producer_brand', 255)->nullable()->comment('Бренд производителя');

            // Product codes and dimensions
            $table->string('ean', 50)->nullable()->comment('EAN код');
            $table->decimal('weight', 6, 3)->nullable()->comment('Вес');
            $table->integer('box_length_cm')->nullable()->comment('Длина коробки');
            $table->integer('box_width_cm')->nullable()->comment('Ширина коробки');
            $table->integer('box_height_cm')->nullable()->comment('Высота коробки');

            // Indexes
            $table->index('reference');
            $table->index('tecdoc_number');
            $table->index('category');
            $table->index('ean');
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
