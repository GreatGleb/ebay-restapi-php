<?php

namespace App\Console\Commands;

use App\Jobs\CollectProductData;
use Illuminate\Console\Command;

class PlanCollectingProductsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:plan-collecting-products-data';

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
        CollectProductData::dispatch();
    }
}
