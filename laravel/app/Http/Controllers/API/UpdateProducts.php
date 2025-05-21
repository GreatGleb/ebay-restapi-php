<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use JetBrains\PhpStorm\NoReturn;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOeCode;
use App\Models\ProductCompatibility;
use App\Models\ProductPhoto;
use App\Models\ProducerBrand;
use App\Models\ProductTecdocData;

class UpdateProducts extends Controller
{
    public function run(Request $request) {
        $products = $request->all();

        $allowedPropertiesInTableProducts = Schema::getColumnListing('products');
        $productsFiltered = collect($products)->map(function ($item) use ($allowedPropertiesInTableProducts) {
            return array_intersect_key($item, array_flip($allowedPropertiesInTableProducts));
        })->toArray();

        $updateFields = array_keys($productsFiltered[0]);

        $resultOfUpdating = Product::upsert($productsFiltered, ['id'], $updateFields);

//            $photos = $product['photos'];
//            $oe_codes = $product['oe_codes'];
//            $car_compatibilities = $product['cars_compatibilities'];

        return [$resultOfUpdating, $updateFields];
    }

    #[NoReturn] public function fromTecDoc(): array
    {
        $products = Product::query()
            ->select('products.*', 'producer_brands.tecdoc_id as producer_tecdoc_id')
            ->leftJoin('producer_brands', function ($join) {
                $join->on(
                    DB::raw('LOWER(products.producer_brand)'),
                    '=',
                    DB::raw('LOWER(producer_brands.name)')
                );
            })
            ->where('products.published_to_ebay_de', false)
            ->orderBy('products.id')
            ->get();

        $requestForTecDoc = [];
        foreach ($products as $product) {
            $brandId = $product->producer_tecdoc_id;
            $reference = $product->tecdoc_number;

            $requestForTecDoc[] = [
                'id' => $product->id,
                'reference' => $reference,
                'brand_id' => $brandId,
            ];
        }

        $requestForTecDoc = [$requestForTecDoc[1]];
//        $requestForTecDoc = [
//            [
//                'id' => 1,
//                'reference' => '82-1127',
//                'brand_id' => 403,
//            ]
//        ];

        $url = "http://ebay_restapi_nginx/tecdoc/products-info";
        $response = Http::timeout(300)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($url, $requestForTecDoc);
        $data = $response->json();
        $resultOfUpdatingProducts = $this->updateDbProductTablesFromTecDoc($data);
//        $data = $response->body();
//        echo $data;
//        dd(null);

        dd($resultOfUpdatingProducts);

        return $results;
    }

    private function updateDbProductTablesFromTecDoc($data): array
    {
        $productUpdateData = [];
        $productUpdateFields = [
            'reference',
            'product_type_ru',
            'product_type_en',
            'product_type_de',
            'specifics_ru',
            'specifics_en',
            'specifics_de',
            'ean',
        ];

        $oeCodesUpdateData = [];
        $compatibilitiesUpdateData = [];
        $photoUpdateData = [];
        $tecdocUpdateData = [];

        foreach ($data as $tecDocProduct) {
            if(isset($tecDocProduct['error']) && $tecDocProduct['error']) {
                echo "error</br>";
                continue;
            }

            $productTypes = $tecDocProduct['productTypes'];
            $specifics = $tecDocProduct['specifics'];

            $productUpdateData[] = [
                'id' => $tecDocProduct['product-id'],
                'reference' => $tecDocProduct['tradeNumbers'][0] ?? $tecDocProduct['articleNumber'],
                'product_type_ru' => $productTypes['ru'],
                'product_type_en' => $productTypes['en'],
                'product_type_de' => $productTypes['de'],
                'specifics_ru' => $specifics['ru'],
                'specifics_en' => $specifics['en'],
                'specifics_de' => $specifics['de'],
                'ean' => $tecDocProduct['ean'],
            ];

            if(isset($tecDocProduct['oemNumbers']) && is_array($tecDocProduct['oemNumbers'])) {
                foreach ($tecDocProduct['oemNumbers'] as $oemNumber) {
                    $oeCodesUpdateData[] = [
                        'product_id' => $tecDocProduct['product-id'],
                        'number' => $oemNumber['articleNumber'],
                        'car_manufacturer_id' => $oemNumber['mfrId'],
                        'car_manufacturer_name' => $oemNumber['mfrName'],
                    ];
                }
            }

            if(
                isset($tecDocProduct['compatibilities'])
                && is_array($tecDocProduct['compatibilities'])
                && !empty($tecDocProduct['compatibilities'])
            ) {
                foreach ($tecDocProduct['compatibilities'] as $value) {
                    $compatibilitiesUpdateData[] = [
                        'product_id' => $tecDocProduct['product-id'],
                        'car_tecdoc_id' => $value
                    ];
                }
            }

            if(
                isset($tecDocProduct['images'])
                && is_array($tecDocProduct['images'])
                && !empty($tecDocProduct['images'])
            ) {
                foreach ($tecDocProduct['images'] as $value) {
                    uksort($value, function($a, $b) {
                        return intval(preg_replace('/[^0-9]/', '', $b)) - intval(preg_replace('/[^0-9]/', '', $a));
                    });

                    $bestImageUrl = reset($value);
                    $photoUpdateData[] = [
                        'product_id' => $tecDocProduct['product-id'],
                        'original_photo_url' => $bestImageUrl
                    ];
                }
            }

            $tecdocUpdateDataItem = [
                'product_id' => $tecDocProduct['product-id'],
                'data_supplier_id' => $tecDocProduct['dataSupplierId'] ?? 0,
                'article_number' => $tecDocProduct['articleNumber'] ?? '',
                'mfr_id' => $tecDocProduct['mfrId'] ?? 0,
                'mfr_name' => $tecDocProduct['mfrName'] ?? '',
            ];

            if(isset($tecDocProduct['totalLinkages']) && $tecDocProduct['totalLinkages'])
                $tecdocUpdateDataItem['total_linkages'] = json_encode($tecDocProduct['totalLinkages']);

            $tecDocJsonFields = [
                ['db_name' => 'misc', 'tecdoc_name' => 'misc'],
                ['db_name' => 'article_text', 'tecdoc_name' => 'articleText'],
                ['db_name' => 'gtins', 'tecdoc_name' => 'gtins'],
                ['db_name' => 'trade_numbers', 'tecdoc_name' => 'tradeNumbers'],
                ['db_name' => 'replaces_articles', 'tecdoc_name' => 'replacesArticles'],
                ['db_name' => 'replaced_by_articles', 'tecdoc_name' => 'replacedByArticles'],
                ['db_name' => 'generic_articles', 'tecdoc_name' => 'genericArticles'],
                ['db_name' => 'article_criteria', 'tecdoc_name' => 'articleCriteria'],
                ['db_name' => 'linkages', 'tecdoc_name' => 'linkages'],
                ['db_name' => 'pdfs', 'tecdoc_name' => 'pdfs'],
                ['db_name' => 'comparable_numbers', 'tecdoc_name' => 'comparableNumbers'],
                ['db_name' => 'search_query_matches', 'tecdoc_name' => 'searchQueryMatches'],
                ['db_name' => 'links', 'tecdoc_name' => 'links'],
                ['db_name' => 'prices', 'tecdoc_name' => 'prices']
            ];

            foreach ($tecDocJsonFields as $field) {
                $tField = $field['tecdoc_name'];
                $dbField = $field['db_name'];
                if(isset($tecDocProduct[$tField]) && $tecDocProduct[$tField])
                    $tecdocUpdateDataItem[$dbField] = json_encode($tecDocProduct[$tField]);
            }

            $tecdocUpdateData[] = $tecdocUpdateDataItem;
        }

        $resultOfUpdatingProducts = Product::upsert($productUpdateData, ['id'], $productUpdateFields);

        if ($oeCodesUpdateData) {
            $productIds = collect($oeCodesUpdateData)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->toArray();

            $resultOfDeletingOeCodes = ProductOeCode::whereIn('product_id', $productIds)->delete();
            $resultOfInsertingOeCodes = ProductOeCode::insert($oeCodesUpdateData);
        }
        if ($compatibilitiesUpdateData) {
            $productIds = collect($compatibilitiesUpdateData)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->toArray();

            $resultOfDeletingCompatibility = ProductCompatibility::whereIn('product_id', $productIds)->delete();
            $resultOfInsertingCompatibility = ProductCompatibility::insert($compatibilitiesUpdateData);
        }
        if ($photoUpdateData) {
            $productIds = collect($photoUpdateData)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->toArray();

            $resultOfDeletingPhotos = ProductPhoto::whereIn('product_id', $productIds)->delete();
            $resultOfInsertingPhotos = ProductPhoto::insert($photoUpdateData);
        }
        if ($tecdocUpdateData) {
            $productIds = collect($tecdocUpdateData)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->toArray();

            $resultOfDeletingTecdocData = ProductTecdocData::whereIn('product_id', $productIds)->delete();
            $resultOfInsertingTecdocData = ProductTecdocData::insert($tecdocUpdateData);
        }

        $results = [
            'updatingProducts' => $resultOfUpdatingProducts,
            'deletingOeCodes' => $resultOfDeletingOeCodes ?? 'empty',
            'insertingOeCodes' => $resultOfInsertingOeCodes ?? 'empty',
            'deletingCompatibility' => $resultOfDeletingCompatibility ?? 'empty',
            'insertingCompatibility' => $resultOfInsertingCompatibility ?? 'empty',
            'deletingPhotos' => $resultOfDeletingPhotos ?? 'empty',
            'insertingPhotos' => $resultOfInsertingPhotos ?? 'empty',
            'deletingTecdocData' => $resultOfDeletingTecdocData ?? 'empty',
            'insertingTecdocData' => $resultOfInsertingTecdocData ?? 'empty'
        ];

        return $results;
    }

    public function brands(): void
    {
        ProducerBrand::insert([
            "name" => "MAXGEAR",
            "tecdoc_id" => 403
        ]);
    }
}
