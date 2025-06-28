<?php

namespace App\Jobs;

use App\Helpers\Log;
use App\Http\Controllers\API\ApiEbayController;
use App\Http\Controllers\API\UpdateAutoPartnerStockAndPrice;
use App\Http\Controllers\API\UpdateProductPrices;
use App\Http\Controllers\API\UpdateProducts;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlanUploadingProductsToEbay implements ShouldQueue
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
    public function handle(): bool
    {
        Log::add($this->logTraceId, 'start PlanUploadingProductsToEbay', 1);

        $updater = new UpdateProducts();
        $isUpdatedFromGoogleSheets = $updater->fromGoogleSheets($this->logTraceId);
        if(!$isUpdatedFromGoogleSheets) {
            return false;
        }

        $isUpdatedUploadingOrder = $updater->setOrderOfUploadingNewProductsToEbay();
        if(!$isUpdatedUploadingOrder) {
            return false;
        }

        $updaterStockAndPrices = new UpdateAutoPartnerStockAndPrice();
        $updaterStockAndPrices->run();

        $updaterPrices = new UpdateProductPrices();
        $updaterPrices->run();

        $ebay = new ApiEbayController();
        $prepared = $ebay->prepareXMLtoAddItems();

        if ($prepared) {
            $limit = (int) (2500/26);

            $queryProducts = Product::
                where('products.published_to_ebay_de', false)
                ->whereNotNull('products.ebay_name_de')
                ->orderBy('products.order_creation_to_ebay_de')
                ->limit($limit);

            $chunksCount = 10;
            $chunkCounter = 0;

            $startHours = 7;
            $endHours = 12;
            $intervalHours = $endHours - $startHours;
            $intervalSeconds = $intervalHours * 60 * 60;

            $timesOfUploading = $queryProducts->count() / $chunksCount;

            $start = Carbon::today('Europe/Berlin')->setHour($startHours)->setMinute(0)->setSecond(0);
            $delayIntervalSeconds = $intervalSeconds / $timesOfUploading; // секунд между загрузками

            $queryProducts->chunk($chunksCount, function ($products) use($timesOfUploading, $start, $delayIntervalSeconds, &$chunkCounter) {
                $productIds = $products->pluck('id')->toJson();

                DB::table('product_uploading_queue')->insert([
                    'product_ids' => $productIds,
                    'place' => 'ebay_de',
                ]);
                $delay = $delayIntervalSeconds * $chunkCounter;

                if($chunkCounter > 0) {
                    return;
                }

                UploadScheduledProductsToEbay::dispatch()->delay($start->copy()->addSeconds($delay));
                $chunkCounter++;
            });
        }

        return true;
    }
}
