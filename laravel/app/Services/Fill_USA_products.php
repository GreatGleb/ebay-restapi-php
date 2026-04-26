<?php

namespace App\Services;

use App\Helpers\Log;
use Illuminate\Support\Facades\Http;

class Fill_USA_products
{
    public function __construct()
    {

    }

    public static function run()
    {
        dump('huy');
        $logTraceId = null;

        $items = ['ASH5072440AB'];
        $requestForTecDoc = [];

        foreach ($items as $reference) {
//            $brandId = 403;

            $requestForTecDoc[] = [
                'reference' => $reference,
//                'brand_id' => $brandId,
            ];
        }

//            $requestForTecDoc = array_slice($requestForTecDoc, 0, 1);

        Log::add($logTraceId, 'send request to tecdoc', 3);

        $url = "http://ebay_restapi_nginx/tecdoc/products-info";
        $response = Http::timeout(21600)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'log-trace-id' => $logTraceId,
            ])->post($url, $requestForTecDoc);

        $data = $response->json();
        dd($data);
    }
}
