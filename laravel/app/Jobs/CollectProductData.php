<?php

namespace App\Jobs;

use App\Helpers\Log;
use App\Http\Controllers\API\UpdateProductPhotos;
use App\Http\Controllers\API\UpdateAutoPartnerStockAndPrice;
use App\Http\Controllers\API\UpdateProductPrices;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Http\Controllers\API\UpdateProducts;

class CollectProductData implements ShouldQueue
{
    use Queueable;

    protected $logTraceId;

    /**
     * Create a new job instance.
     */
    public function __construct($logTraceId = null)
    {
        $this->logTraceId = $logTraceId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        Log::add($this->logTraceId, 'start collecting data for new products by 30 products at chunk', 1);

        $updater = new UpdateProducts();
        $isUpdatedFromGoogleSheets = $updater->fromGoogleSheets($this->logTraceId);
        if(!$isUpdatedFromGoogleSheets) {
            return false;
        }

        $queryProducts = Product::query()
            ->where('products.published_to_ebay_de', false)
            ->whereNull('products.reference')
            ->whereNotNull('products.tecdoc_number')
            ->orderBy('products.id');

        $productsCount = $queryProducts->count();

        var_dump($queryProducts->pluck('id'));
        var_dump('product ids');
        Log::add($this->logTraceId, "Products number: $productsCount", 2);
        var_dump($productsCount);
        var_dump("Products number");

        $chunkCounter = 0;

        $queryProducts->chunk(30, function ($products) use(&$chunkCounter, $updater) {
            var_dump($chunkCounter);
            var_dump('$chunkCounter');
            Log::add($this->logTraceId, "start chunk #$chunkCounter", 2);
            $productIds = $products->pluck('id')->toArray();

            $isUpdatedFromApNext = $updater->fromApNextEu($this->logTraceId, $productIds);
            if (!$isUpdatedFromApNext) {
                Log::add($this->logTraceId, "stop fromApNextEu", 2);
                return false;
            }

            $isUpdatedFromTecDoc = $updater->fromTecDoc($this->logTraceId, $productIds);
            if (!$isUpdatedFromTecDoc) {
                Log::add($this->logTraceId, "stop fromTecDoc", 2);
                return false;
            }

            $isUpdatedFromEbay = $updater->fromEbay($this->logTraceId, $productIds);
            if (!$isUpdatedFromEbay) {
                Log::add($this->logTraceId, "stop fromEbay", 2);
                return false;
            }

            $updaterPhotos = new UpdateProductPhotos();
            $isUpdatedFromPhotos = $updaterPhotos->run($this->logTraceId, $productIds);
            if (!$isUpdatedFromPhotos) {
                Log::add($this->logTraceId, "stop fromPhotos", 2);
                return false;
            }

            $chunkCounter++;
        });

        $updaterStockAndPrices = new UpdateAutoPartnerStockAndPrice();
        $updaterStockAndPrices->run();

        $updaterPrices = new UpdateProductPrices();
        $updaterPrices->run();

        $isUpdatedToGoogleSheets = $updater->toGoogleSheets($this->logTraceId);

        return $isUpdatedToGoogleSheets;
    }
}
