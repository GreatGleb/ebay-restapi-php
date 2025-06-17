<?php

namespace App\Jobs;

use App\Http\Controllers\API\ApiEbayController;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class UploadScheduledProductsToEbay implements ShouldQueue
{
    use Queueable;

    protected $logTraceId;

    /**
     * Create a new job instance.
     */
    public function __construct($logTraceId)
    {
        $this->logTraceId = $logTraceId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $productIds = DB::table('product_uploading_queue')->where('place', 'ebay_de')->first();
        $productIds = $productIds->product_ids;
        $productIds = json_decode($productIds, true);

        $ebay = new ApiEbayController();
        $ebay->publicPreparedItemsToEbay($productIds);
    }
}
