<?php

namespace App\Console\Commands;

use App\Jobs\UpdateProductPricesInEbay as JobUpdateProductPricesInEbay;
use Illuminate\Console\Command;

class UpdateProductPricesInEbay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-product-prices-in-ebay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobUpdateProductPricesInEbay::dispatch();
    }
}
