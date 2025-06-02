<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOeCode;
use App\Models\ProductCompatibility;
use App\Models\ProductPhoto;
use App\Models\ProducerBrand;
use App\Models\ProductTecdocData;
use App\Helpers\Log;

class UpdateProductPhotos extends Controller
{
    public function run($logTraceId = null): bool
    {
        Log::add($logTraceId, 'start work', 1);
        Log::add($logTraceId, 'get photos what from tecalliance', 2);

        $queryProductPhotos = ProductPhoto
            ::where('product_photos.original_photo_url', 'like', '%digital-assets.tecalliance.services%')
            ->orderBy('id');

        $countOfPhotos = $queryProductPhotos->count();

        Log::add($logTraceId, 'got' . $countOfPhotos . ' photos what from tecalliance', 2);

        $photoCounters = [];

        $chunkKey = 1;
        $queryProductPhotos->chunk(10, function ($photosChunk) use ($logTraceId, &$chunkKey, &$photoCounters) {
            if($chunkKey != 2) {

                $chunkKey++;
                return;
            }

            Log::add($logTraceId, 'start chunk ' . $chunkKey . ' by 10 photos', 2);
            Log::add($logTraceId, 'prepare request for microservice', 3);

            $requestForMicroService = [];

            foreach ($photosChunk as $photo) {
                $productId = $photo->product_id;

                if (!isset($photoCounters[$productId])) {
                    $photoCounters[$productId] = 1;
                }

                $photoIndex = $photoCounters[$productId];

                $filename = "{$productId}_{$photoIndex}";

                $photoCounters[$productId]++;

                $requestForMicroService[] = [
                    'id' => $photo->id,
                    '$productId' => $productId,
                    'name' => $filename,
                    'url' => $photo->original_photo_url,
                ];
            }

            Log::add($logTraceId, 'send request to photo uploader', 3);

            $url = "http://ebay_restapi_nginx/photo-upload/add";
            $response = Http::timeout(300)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'log-trace-id' => $logTraceId,
                ])->post($url, $requestForMicroService);

            $data = $response->json();

            dd($data);

            if($data) {
                Log::add($logTraceId, 'update db by tecdoc data', 3);
//                $this->updateDbProductTablesFromTecDoc($data, $logTraceId);
            }

            $chunkKey = $chunkKey + 1;

            Log::add($logTraceId, 'finish chunk', 3);
        });

        Log::add($logTraceId, 'finish work', 1);

        return true;
    }
}
