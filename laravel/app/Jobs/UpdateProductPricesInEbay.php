<?php

namespace App\Jobs;

use App\Helpers\Log;
use App\Http\Controllers\API\ApiEbayController;
use App\Http\Controllers\API\UpdateProducts;
use App\Http\Controllers\API\UpdateAutoPartnerStockAndPrice;
use App\Http\Controllers\API\UpdateProductPrices;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateProductPricesInEbay implements ShouldQueue
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
        Log::add($this->logTraceId, 'start updating prices', 1);

        $updaterStockAndPrices = new UpdateAutoPartnerStockAndPrice();
        $updaterStockAndPrices->run();
        $updaterPrices = new UpdateProductPrices();
        $updaterPrices->run();

        $ebay = new ApiEbayController();
        $isUpdatedToEbay = $ebay->updateStockAndPrice();
        if(!$isUpdatedToEbay) {
            return false;
        }

        $updater = new UpdateProducts();
        $isUpdatedToGoogleSheets = $updater->toGoogleSheets($this->logTraceId);

        return $isUpdatedToGoogleSheets;
    }
}
