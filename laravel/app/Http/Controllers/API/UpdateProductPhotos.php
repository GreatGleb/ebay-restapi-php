<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\Controller;
use App\Models\ProductPhoto;
use App\Helpers\Log;

class UpdateProductPhotos extends Controller
{
    public function run($logTraceId = null, $productIds = []): bool
    {
        Log::add($logTraceId, 'start work UpdateProductPhotos', 1);
        Log::add($logTraceId, 'get photos what from tecalliance', 2);

        $queryProductPhotos = ProductPhoto
            ::where('product_photos.original_photo_url', 'like', '%digital-assets.tecalliance.services%')
            ->orderBy('id');

        if($productIds) {
            $queryProductPhotos = ProductPhoto::query()
                ->whereIn('product_id', $productIds)
                ->orderBy('id');
        }

        $countOfPhotos = $queryProductPhotos->count();

        Log::add($logTraceId, 'got' . $countOfPhotos . ' photos what from tecalliance', 2);

        $chunkKey = 1;
        $queryProductPhotos->chunk(10, function ($photosChunk) use ($logTraceId, &$chunkKey) {
            Log::add($logTraceId, 'start chunk ' . $chunkKey . ' by 10 photos', 2);
            Log::add($logTraceId, 'prepare request for microservice', 3);

            $requestForMicroService = [];

            foreach ($photosChunk as $photo) {
                $productId = $photo->product_id;

                $photoWithOneProduct = ProductPhoto::where('product_id', $productId)->get();
                $photoWithOneProductIndex = $photoWithOneProduct->pluck('id')->toArray();
                $photoWithOneProductIndex = array_flip($photoWithOneProductIndex);

                $photoIndex = $photoWithOneProductIndex[$photo->id] + 1;

                $filename = "{$productId}_{$photoIndex}";

                $requestForMicroService[] = [
                    'id' => $photo->id,
                    'product_id' => $productId,
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

            if($data) {
                Log::add($logTraceId, 'update db by tecdoc data', 3);
                $this->updateDb($data, $logTraceId);
            }

            $chunkKey = $chunkKey + 1;

            Log::add($logTraceId, 'finish chunk', 3);
        });

        Log::add($logTraceId, 'finish work UpdateProductPhotos', 1);

        return true;
    }

    public function updateDb($data, $logTraceId = null) {
        Log::add($logTraceId, 'started update db', 3);

        if($data['result']) {
            $updateData = [];
            $updateFields = [
                'original_photo_url',
                'cortexparts_photo_url',
            ];

            foreach ($data['items'] as $item) {
                $original_photo_url = '';
                $cortexparts_photo_url = null;

                if($item['original_photo_url']) {
                    $original_photo_url = 'https://cortexparts.github.io/photo' . $item['original_photo_url'];
                }

                if($item['cortexparts_photo_url']) {
                    $cortexparts_photo_url = 'https://cortexparts.github.io/photo' . $item['cortexparts_photo_url'];
                }

                $updateData[] = [
                    'id' => $item['id'],
                    'product_id' => $item['product_id'],
                    'original_photo_url' => $original_photo_url,
                    'cortexparts_photo_url' => $cortexparts_photo_url,
                ];
            }

            $resultOfUpdatingProducts = ProductPhoto::upsert($updateData, ['id'], $updateFields);
        }

        return $resultOfUpdatingProducts ?? false;
    }
}
