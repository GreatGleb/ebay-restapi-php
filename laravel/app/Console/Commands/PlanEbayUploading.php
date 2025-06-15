<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\ApiEbayController;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlanEbayUploading extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:plan-ebay-uploading';

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
        $ebay = new ApiEbayController();
        $prepared = $ebay->prepareXMLtoAddItems();

        if ($prepared) {
            $limit = (int) 2500/26;

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

            $start = Carbon::today('Europe/Berlin')->setHour($startHours)->setMinute(0)->setSecond(0); // 9:00 утра
            $delayIntervalSeconds = $intervalSeconds / $timesOfUploading; // секунд между загрузками

            $queryProducts->chunk($chunksCount, function ($products) use($timesOfUploading, $start, $delayIntervalSeconds, &$chunkCounter) {
                $productIds = $products->pluck('id')->toJson();

                DB::table('product_uploading_schedule')->insert([
                    'product_ids' => $productIds,
                    'place' => 'ebay_de',
                ]);

                $delay = $delayIntervalSeconds * $chunkCounter;
                UploadScheduledProductsToEbay::dispatch()->delay($start->copy()->addSeconds($delay));

                $chunkCounter++;

//            $preparedItems = DB::table('product_prepared_xml_for_upload_to_ebay')
//                ->whereIn('product_id', $productIds)
//                ->get()
//                ->groupBy('product_id')
//                ->toArray();
            });
        }
    }
}
