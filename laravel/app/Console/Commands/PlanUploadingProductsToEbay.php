<?php

namespace App\Console\Commands;

use App\Jobs\PlanUploadingProductsToEbay as JobPlanUploadingProductsToEbay;
use Illuminate\Console\Command;

class PlanUploadingProductsToEbay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:plan-ebay-uploading-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Plan items to upload ebay';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobPlanUploadingProductsToEbay::dispatch();
    }
}
