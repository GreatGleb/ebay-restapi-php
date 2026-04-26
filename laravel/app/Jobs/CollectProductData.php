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
        var_dump($isUpdatedFromGoogleSheets);
        var_dump('$isUpdatedFromGoogleSheets');
        if(!$isUpdatedFromGoogleSheets) {
            Log::add($this->logTraceId, 'can\'t update from google sheets - stop and exit', 1);
            dump('can\'t update from google sheets - stop and exit');

            return false;
        }

        $queryProducts = Product::query()
            ->where('products.published_to_ebay_de', false)
//            ->whereNull('products.reference')
//            ->whereNotNull('products.tecdoc_number')
            ->where(function ($query) {
                $query->whereNull('products.part_of_ebay_name_for_cars')
                    ->orWhereNull('products.part_of_ebay_de_name_product_type');
            })
            ->whereNull('products.ebay_name_de')
            ->orderBy('products.id');

//       ↓ test
//        $queryProducts = Product::query()
////            ->with('ebaySimilarProducts')
////            ->whereHas('ebaySimilarProducts')
//            ->where('products.published_to_ebay_de', false)
//            ->whereNull('products.part_of_ebay_name_for_cars')
//            ->whereNull('products.ebay_name_de')
//            ->orderBy('products.id');
//       ↑ test

        $productsCount = $queryProducts->count();

        dump($queryProducts->pluck('id')->toArray());
        dump('product ids');
        Log::add($this->logTraceId, "Products number: $productsCount", 2);
        dump($productsCount);
        dump("Products number");

        $chunkCounter = 0;

        $queryProducts->chunk(30, function ($products) use(&$chunkCounter, $updater) {
            var_dump($chunkCounter);
            var_dump('$chunkCounter');
            Log::add($this->logTraceId, "start chunk #$chunkCounter", 2);
            $productIds = $products->pluck('id')->toArray();

//            dump($products->toArray());

            $isUpdatedFromApNext = $updater->fromApNextEu($this->logTraceId, $productIds);
            if (!$isUpdatedFromApNext) {
                dump("stop fromApNextEu");
                Log::add($this->logTraceId, "stop fromApNextEu", 2);
                return false;
            }

            $isUpdatedFromTecDoc = $updater->fromTecDoc($this->logTraceId, $productIds);
            if (!$isUpdatedFromTecDoc) {
                dump("stop fromTecDoc");
                Log::add($this->logTraceId, "stop fromTecDoc", 2);
                return false;
            }

            $isUpdatedFromEbay = $updater->fromEbay($this->logTraceId, $productIds);
            if (!$isUpdatedFromEbay) {
                Log::add($this->logTraceId, "stop fromEbay", 2);
                return false;
            }

            $isUpdatedFromGemini = $updater->fromGemini($this->logTraceId, $productIds);
            if (!$isUpdatedFromGemini) {
                Log::add($this->logTraceId, "stop Gemini", 2);
                return false;
            }

            $updaterPhotos = new UpdateProductPhotos();
            $isUpdatedFromPhotos = $updaterPhotos->run($this->logTraceId, $productIds);
            if (!$isUpdatedFromPhotos) {
                Log::add($this->logTraceId, "stop fromPhotos", 2);
                return false;
            }

//            return false;

            $chunkCounter++;
        });

//        $updaterStockAndPrices = new UpdateAutoPartnerStockAndPrice();
//        $updaterStockAndPrices->run();
//
//        $updaterPrices = new UpdateProductPrices();
//        $updaterPrices->run();

        $isUpdatedToGoogleSheets = $updater->toGoogleSheets($this->logTraceId);

        return $isUpdatedToGoogleSheets;
    }
}
