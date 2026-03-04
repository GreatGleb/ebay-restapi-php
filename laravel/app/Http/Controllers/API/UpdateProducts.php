<?php

namespace App\Http\Controllers\API;

use App\Helpers\EbayData;
use App\Services\ArtificialIntelligenceService;
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

class UpdateProducts extends Controller
{
    public function run(Request $request) {
        $products = $request->all();

//        $allowedPropertiesInTableProducts = Schema::getColumnListing('products');
        $allowedPropertiesInTableProducts = [
            'id',
            'reference',
            'tecdoc_number',
            'supplier_price_net',
            'stock_quantity_pl',
            'stock_quantity_pruszkow',
            'internal_description',
            'part_of_ebay_de_name_product_type',
            'part_of_ebay_name_for_cars',
            'ebay_name_ru',
            'ebay_name_de',
            'has_hologram',
            'supplier',
            'box_length_cm',
            'box_width_cm',
            'box_height_cm',
            'comment',
            'sold_in_general',
        ];

        $productsFiltered = collect($products)->map(function ($item) use ($allowedPropertiesInTableProducts) {
            return array_intersect_key($item, array_flip($allowedPropertiesInTableProducts));
        })->toArray();

        foreach ($productsFiltered as &$product) {
            if (!isset($product['supplier']) or !$product['supplier']) {
                $product['supplier'] = 'AutoPartner';
            }
        }

        $updateFields = array_keys($productsFiltered[0]);

        $resultOfUpdating = Product::upsert($productsFiltered, ['id'], $updateFields);

//            $photos = $product['photos'];
//            $oe_codes = $product['oe_codes'];
//            $car_compatibilities = $product['cars_compatibilities'];

        return [$resultOfUpdating, $updateFields];
    }

    public function fromTecDoc($logTraceId = null, $productIds = []): bool
    {
        Log::add($logTraceId, 'start work fromTecDoc', 1);
        Log::add($logTraceId, 'get products what not published to ebay', 2);

        $queryProducts = Product::query()
            ->select('products.*', 'producer_brands.tecdoc_id as producer_tecdoc_id')
            ->leftJoin('producer_brands', function ($join) {
                $join->on(
                    DB::raw('LOWER(products.producer_brand)'),
                    '=',
                    DB::raw('LOWER(producer_brands.name)')
                );
            })
            ->where('products.published_to_ebay_de', false)
            ->whereNull('products.reference')
            ->orderBy('products.id');

        if($productIds) {
            $queryProducts = Product::query()
                ->select('products.*', 'producer_brands.tecdoc_id as producer_tecdoc_id')
                ->leftJoin('producer_brands', function ($join) {
                    $join->on(
                        DB::raw('LOWER(products.producer_brand)'),
                        '=',
                        DB::raw('LOWER(producer_brands.name)')
                    );
                })
                ->whereIn('products.id', $productIds)
                ->orderBy('products.id');
        }

        $countOfProducts = $queryProducts->count();

        Log::add($logTraceId, 'got' . $countOfProducts . ' products what not published to ebay', 2);

        $chunkKey = 1;
        $queryProducts->chunk(10, function ($products) use ($logTraceId, &$chunkKey) {
            Log::add($logTraceId, 'start chunk ' . $chunkKey . ' by 10 products', 2);
            Log::add($logTraceId, 'prepare request for tecdoc', 3);

            $requestForTecDoc = [];

            foreach ($products as $product) {
                $brandId = $product->producer_tecdoc_id;
                $reference = $product->tecdoc_number;

                if(!$brandId) {
                    $brandId = 403;
                }

                $requestForTecDoc[] = [
                    'id' => $product->id,
                    'reference' => $reference,
                    'brand_id' => $brandId,
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

            if($data) {
                Log::add($logTraceId, 'update db by tecdoc data', 3);
                $this->updateDbProductTablesFromTecDoc($data, $logTraceId);
            }

            $chunkKey = $chunkKey + 1;

            Log::add($logTraceId, 'finish chunk', 3);
        });

        Log::add($logTraceId, 'finish work fromTecDoc', 1);

        return true;
    }

    private function updateDbProductTablesFromTecDoc($data, $logTraceId): array
    {
        $productUpdateData = [];
        $productUpdateFields = [
            'reference',
            'product_type_ru',
            'product_type_en',
            'product_type_de',
            'installation_position_ru',
            'installation_position_en',
            'installation_position_de',
            'specifics_ru',
            'specifics_en',
            'specifics_de',
            'ean',
            'producer_brand',
            'no_photo',
        ];

        $oeCodesUpdateData = [];
        $compatibilitiesUpdateData = [];
        $photoUpdateData = [];
        $tecdocUpdateData = [];

        Log::add($logTraceId, 'start preparing foreach', 3);

        foreach ($data as $key => $tecDocProduct) {
            Log::add($logTraceId, 'foreach ' . $key, 4);

            if(isset($tecDocProduct['error']) && $tecDocProduct['error']) {
                echo "error</br>";
                Log::add($logTraceId, 'foreach error product-id:' . $tecDocProduct['product-id'], 4);
                continue;
            }

            $productTypes = $tecDocProduct['productTypes'];
            $specifics = $tecDocProduct['specifics'];
            $installationPosition = $this->getInstallationPositionFromSpecifics($specifics);

            $noPhoto = (!isset($tecDocProduct['images']) or !is_array($tecDocProduct['images']) or empty($tecDocProduct['images']));

            $productUpdateData[] = [
                'id' => $tecDocProduct['product-id'],
                'reference' => $tecDocProduct['tradeNumbers'][0] ?? $tecDocProduct['articleNumber'],
                'product_type_ru' => $productTypes['ru'],
                'product_type_en' => $productTypes['en'],
                'product_type_de' => $productTypes['de'],
                'installation_position_ru' => $installationPosition['ru'],
                'installation_position_en' => $installationPosition['en'],
                'installation_position_de' => $installationPosition['de'],
                'specifics_ru' => $specifics['ru'],
                'specifics_en' => $specifics['en'],
                'specifics_de' => $specifics['de'],
                'ean' => $tecDocProduct['ean'],
                'no_photo' => $noPhoto,
                'producer_brand' => $tecDocProduct['mfrName'] ?? '',
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

            if(!$noPhoto) {
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
                $tecdocUpdateDataItem['total_linkages'] = $tecDocProduct['totalLinkages'];
            else
                $tecdocUpdateDataItem['total_linkages'] = 0;

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
                else
                    $tecdocUpdateDataItem[$dbField] = '[]';
            }

            $tecdocUpdateData[] = $tecdocUpdateDataItem;
        }

        Log::add($logTraceId, 'update db products', 3);
        $resultOfUpdatingProducts = Product::upsert($productUpdateData, ['id'], $productUpdateFields);

        if ($oeCodesUpdateData) {
            Log::add($logTraceId, 'update db oecodes', 3);
            $productIds = collect($oeCodesUpdateData)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->toArray();

            $resultOfDeletingOeCodes = ProductOeCode::whereIn('product_id', $productIds)->delete();
            $resultOfInsertingOeCodes = ProductOeCode::insert($oeCodesUpdateData);
        }
        if ($compatibilitiesUpdateData) {
            Log::add($logTraceId, 'update db compatibilities', 3);
            $productIds = collect($compatibilitiesUpdateData)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->toArray();

            $resultOfDeletingCompatibility = ProductCompatibility::whereIn('product_id', $productIds)->delete();
            $resultOfInsertingCompatibility = ProductCompatibility::insert($compatibilitiesUpdateData);
        }
        if ($photoUpdateData) {
            Log::add($logTraceId, 'update db photos', 3);
            $productIds = collect($photoUpdateData)
                ->pluck('product_id')
                ->unique()
                ->values()
                ->toArray();

            $productIdsWithPhotos = ProductPhoto::whereIn('product_id', $productIds)
                ->pluck('product_id')
                ->unique()
                ->toArray();

            $filteredPhotoUpdateData = collect($photoUpdateData)
                ->reject(function ($photo) use ($productIdsWithPhotos) {
                    return in_array($photo['product_id'], $productIdsWithPhotos);
                })
                ->values()
                ->toArray();

            if($filteredPhotoUpdateData) {
                $resultOfInsertingPhotos = ProductPhoto::insert($filteredPhotoUpdateData);
            }

//            $resultOfDeletingPhotos = ProductPhoto::whereIn('product_id', $productIds)->delete();
//            $resultOfInsertingPhotos = ProductPhoto::insert($photoUpdateData);
        }
        if ($tecdocUpdateData) {
            Log::add($logTraceId, 'update db tecdoc data', 3);
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

    private function getInstallationPositionFromSpecifics($specifics)
    {
        $searchedParameter = [
            'ru' => 'Сторона установки',
            'en' => 'Fitting Position',
            'de' => 'Einbauposition',
        ];

        $result = [];
        foreach ($specifics as $lang => $specific) {
            $result[$lang] = '';
            if($specific) {
                $lines = preg_split("/\r\n|\n|\r/", $specific);
                $lines = array_map(function($line) {
                    return rtrim($line, ",");
                }, $lines);

                foreach ($lines as $line) {
                    $param = explode(" - ", $line);
                    $paramName = $param[0];
                    $paramValue = $param[1];

                    if($paramName == $searchedParameter[$lang]) {
                        $result[$lang] = $paramValue;
                        break;
                    }
                }
            }
        }

        return $result;
    }

    public function fromGoogleSheets($logTraceId = null): bool
    {
        Log::add($logTraceId, 'start updating DB from Google Sheets', 1);

        $url = "http://ebay_restapi_nginx/python/products/save_to_db_from_google_sheets";
        $response = Http::timeout(300)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'log-trace-id' => $logTraceId,
            ])
            ->get($url);

        $result = true;

        if (!$response->successful()) {
            Log::add($logTraceId, 'request failed', 2);

            $result = false;
        } else {
            Log::add($logTraceId, 'response: ' . $response->body(), 2);
        }

        Log::add($logTraceId, 'finish updating DB from Google Sheets', 1);

        return $result;
    }

    public function toGoogleSheets($logTraceId = null): bool
    {
        Log::add($logTraceId, 'start updating DB to Google Sheets', 1);
        dump('start updating DB to Google Sheets');

        $url = "http://ebay_restapi_nginx/python/products/update_from_db_to_google_sheets";
        $response = Http::timeout(300)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'log-trace-id' => $logTraceId,
            ])
            ->get($url);

        $result = true;

        if (!$response->successful()) {
            Log::add($logTraceId, 'request failed', 2);
            dump('request failed');

            $result = false;
        } else {
            Log::add($logTraceId, 'response: ' . $response->body(), 2);
            dump('response: ' . $response->body());
        }

        Log::add($logTraceId, 'finish updating DB to Google Sheets', 1);

        return $result;
    }

    public function fromEbay($logTraceId = null, $productIds = []): bool
    {
        Log::add($logTraceId, 'start work fromEbay', 1);
        Log::add($logTraceId, 'get products what not published to ebay', 2);

        $queryProducts = Product::query()
            ->where('products.published_to_ebay_de', false)
            ->whereNotNull('products.ean')
            ->orderBy('products.id');

        if($productIds) {
            $queryProducts = Product::query()
                ->whereNotNull('ean')
                ->whereIn('id', $productIds)
                ->orderBy('id');
        }

        $countOfProducts = $queryProducts->count();

        Log::add($logTraceId, 'got' . $countOfProducts . ' products what not published to ebay', 2);

        $ebayClass = new ApiEbayController();

        $chunkKey = 1;
        $queryProducts->chunk(10, function ($products) use ($logTraceId, $ebayClass, &$chunkKey) {
            Log::add($logTraceId, 'start chunk ' . $chunkKey . ' by 10 products', 2);
            Log::add($logTraceId, 'prepare request for tecdoc', 3);

            $requestForEbay = [];

            foreach ($products as $product) {
                $requestForEbay[] = [
                    'id' => $product->id,
                    'reference' => $product->reference,
                    'ean' => $product->ean,
                ];
            }

            Log::add($logTraceId, 'send request to ebay', 3);

            $data = $ebayClass->searchItemsByProducts($requestForEbay);

            if($data) {
                Log::add($logTraceId, 'update db by ebay data', 3);
                $this->updateDbProductTablesFromEbay($data, $logTraceId);
            }

            $chunkKey = $chunkKey + 1;

            Log::add($logTraceId, 'finish chunk', 3);
        });

        Log::add($logTraceId, 'finish work fromEbay', 1);

        return true;
    }

    private function updateDbProductTablesFromEbay($data, $logTraceId): array
    {
        $productUpdateData = [];
        $productUpdateFields = [
            'category_id_ebay_de',
            'ru_category_from_ebay_de',
        ];

        $ebaySimilarProductsUpdateData = [];

        $categoryIds = collect($data)
            ->pluck('categoryId')
            ->unique()
            ->values()
            ->toArray();

        $categories = \DB::table('categories')
            ->whereIn('ebay_de_id', $categoryIds)
            ->pluck('full_name_ru', 'ebay_de_id')
            ->toArray();

        Log::add($logTraceId, 'start preparing foreach', 3);

        foreach ($data as $product) {
            Log::add($logTraceId, 'foreach id:' . $product['product-id'], 4);

            if(isset($product['error']) && $product['error']) {
                echo "error</br>";
                Log::add($logTraceId, 'foreach error id:' . $product['product-id'], 4);
                continue;
            }

            $productUpdateData[] = [
                'id' => $product['product-id'],
                'category_id_ebay_de' => $product['categoryId'],
                'ru_category_from_ebay_de' => $categories[$product['categoryId']] ?? null,
            ];

            $ebaySimilarProductsUpdateData[] = [
                'product_id' => $product['product-id'],
                'names' => json_encode($product['names']),
                'prices' => json_encode($product['prices']),
                'specifics' => json_encode($product['specifics']),
                'photo' => $product['photo'],
            ];
        }

        Log::add($logTraceId, 'update db products', 3);
        $resultOfUpdatingProducts = Product::upsert($productUpdateData, ['id'], $productUpdateFields);

        Log::add($logTraceId, 'update db ebay similar products', 3);
        $productIds = collect($ebaySimilarProductsUpdateData)
            ->pluck('product_id')
            ->unique()
            ->values()
            ->toArray();

        $resultOfDeletingEbaySimilarProducts = \DB::table('product_ebay_similar_products')->whereIn('product_id', $productIds)->delete();
        $resultOfInsertingEbaySimilarProducts = \DB::table('product_ebay_similar_products')->insert($ebaySimilarProductsUpdateData);

        $results = [
            'updatingProducts' => $resultOfUpdatingProducts,
            'deletingEbaySimilarProducts' => $resultOfDeletingEbaySimilarProducts ?? 'empty',
            'insertingEbaySimilarProducts' => $resultOfInsertingEbaySimilarProducts ?? 'empty',
        ];

        return $results;
    }

    public function fromApNextEu($logTraceId = null, $productIds = []): bool
    {
        Log::add($logTraceId, 'start work fromApNextEu', 1);
        Log::add($logTraceId, 'get products what not published to ebay', 2);

        $queryProducts = Product::query()
            ->where('products.published_to_ebay_de', false)
            ->whereNull('products.name_original_pl')
            ->orderBy('products.id');

        if($productIds) {
            $queryProducts = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('products.id');
        }

        $countOfProducts = $queryProducts->count();

        Log::add($logTraceId, 'got' . $countOfProducts . ' products what not published to ebay', 2);

        $chunkKey = 1;
        $queryProducts->chunk(10, function ($products) use ($logTraceId, &$chunkKey) {
            Log::add($logTraceId, 'start chunk ' . $chunkKey . ' by 10 products', 2);
            Log::add($logTraceId, 'prepare request for scrapping', 3);

            $requestForScrapping = [];

            foreach ($products as $product) {
                $reference = $product->tecdoc_number;

                $requestForScrapping[] = [
                    'id' => $product->id,
                    'reference' => $reference,
                ];

                Log::add($logTraceId, 'prepare product ' . $product->id . " " . $reference, 3);
            }

            Log::add($logTraceId, 'send request to scrapping', 3);
            Log::add($logTraceId, json_encode($requestForScrapping, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 3);

            $url = "http://ebay_restapi_nginx/selenium/products";
            $response = Http::timeout(300)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'log-trace-id' => $logTraceId,
                ])->post($url, $requestForScrapping);

            $data = $response->json();

//            $data = [
//                "result" => true,
//                "items" => [
//                    [
//                        "id" => 35,
//                        "reference" => "7175682",
//                        "parsed" => [
//                            "link" => "https://www.apnext.eu/pl/wyszukiwanie/1/1/7175682/pokrywa-mostu-iveco-daily-06/5734087",
//                            "name" => "POKRYWA MOSTU IVECO DAILY 06-",
//                            "brand_id" => "0",
//                            "main_stock" => 2,
//                            "second_stock" => 2,
//                            "price" => 28.54,
//                        ],
//                    ],
//                ],
//            ];

            if($data) {
                var_dump($data);
                var_dump("apnexteu data");
                Log::add($logTraceId, 'update db by apnexteu data', 3);
                $this->updateDbProductTablesFromApNextEu($data, $logTraceId);
            } else {
                Log::add($logTraceId, 'empty apnexteu data', 3);
            }

            $chunkKey = $chunkKey + 1;

            Log::add($logTraceId, 'finish chunk', 3);
        });

        Log::add($logTraceId, 'finish work fromApNextEu', 1);

        return true;
    }

    private function updateDbProductTablesFromApNextEu($data, $logTraceId)
    {
        $productUpdateData = [];
        $productUpdateFields = [
            'supplier_price_net',
            'name_original_pl',
            'link',
            'stock_quantity_pl',
            'stock_quantity_pruszkow',
            'producer_brand',
        ];

        if(!$data['items']) {
            return false;
        }

        $brandIds = array_unique(array_map(function($item) {
            return $item['parsed']['brand_id'] ?? null;
        }, $data['items']));

        $brands = ProducerBrand::whereIn('tecdoc_id', $brandIds)->pluck('name', 'tecdoc_id')->toArray();

        Log::add($logTraceId, 'start preparing foreach', 3);

        foreach ($data['items'] as $item) {
            if(!isset($item['parsed']['link'])) {
                continue;
            }

            $producerBrand = null;

            if(isset($item['parsed']['brand_id'])) {
                try {
                    $producerBrand = (int) $item['parsed']['brand_id'];
                    $producerBrand = $brands[$producerBrand] ?? null;
                } catch (Exception $e) {
                    Log::add($logTraceId, 'Error while try get brand from parced item ' . $e->getMessage(), 3);
                }
            }

            Log::add($logTraceId, 'parsed ' . $item['id'], 3);

            $productUpdateData[] = [
                'id' => $item['id'],
                'supplier_price_net' => $item['parsed']['price'] ?? null,
                'name_original_pl' => $item['parsed']['name'] ?? null,
                'link' => $item['parsed']['link'] ?? null,
                'stock_quantity_pl' => $item['parsed']['main_stock'] ?? 0,
                'stock_quantity_pruszkow' => $item['parsed']['second_stock'] ?? 0,
                'producer_brand' => $producerBrand,
            ];
        }

        Log::add($logTraceId, 'update db products', 3);
        $resultOfUpdatingProducts = Product::upsert($productUpdateData, ['id'], $productUpdateFields);

        return $resultOfUpdatingProducts;
    }

    public function setOrderOfUploadingNewProductsToEbay()
    {
        $products = Product::
            where('products.published_to_ebay_de', false)
            ->whereNotNull('products.product_type_en')
            ->orderBy('products.id')
            ->get();

        $productsGroup = [];

        foreach ($products as $product) {
            $productType = $product->product_type_en;

            if(!isset($productsGroup[$productType])) {
                $productsGroup[$productType] = [];
            }

            $productsGroup[$productType][] = $product->id;
        }

        $productsIdInOrder = [];

        foreach ($productsGroup as $productType => $productIds) {
            while($productsGroup[$productType]) {
                foreach ($productsGroup as $productType2 => $productIds2) {
                    $productId = array_shift($productsGroup[$productType2]);

                    if ($productId) {
                        $item = [
                            'type' => $productType2,
                            'id' => $productId,
                        ];

                        $productsIdInOrder[] = $item;
                    }
                }
            }
        }

        $dbRequest = [];

        foreach ($productsIdInOrder as $key => $item) {
            $dbRequest[] = [
                'id' => $item['id'],
                'order_creation_to_ebay_de' => $key
            ];
        }

        $resultOfUpdating = Product::upsert($dbRequest, ['id'], ['order_creation_to_ebay_de']);

//        $products2 = Product::
//            where('products.published_to_ebay_de', false)
//            ->whereNotNull('products.product_type_en')
//            ->whereNotNull('products.order_creation_to_ebay_de')
//            ->orderBy('products.order_creation_to_ebay_de')
//            ->get();
//
//        foreach ($products2 as $item) {
//            $photo = ProductPhoto::where('product_id', $item['id'])->first();
//            if($photo and $photo->cortexparts_photo_url) {
//                $photo = $photo->cortexparts_photo_url;
//
//                echo '<img src="' . $photo . '" width="50px" />';
//            }
//
//            echo $item->product_type_en . ' ' . $item['id'] . "<br>";
//        }

        return $resultOfUpdating;
    }

    public function brands(): void
    {
        $logTraceId = null;

        $url = "http://ebay_restapi_nginx/tecdoc/get-all-brands";
        $response = Http::timeout(300)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'log-trace-id' => $logTraceId,
            ])->get($url);

        $data = $response->json();

        if($data) {
            $insertData = [];

            foreach ($data as $brand) {
                $insertData[] = [
                    'name' => $brand['brandName'],
                    'tecdoc_id' => $brand['brandId'],
                ];
            }

            ProducerBrand::insert($insertData);
        }
    }

    public function fromGemini($logTraceId = null, $productIds = []): bool
    {
        Log::add($logTraceId, 'start work Gemini', 1);
        Log::add($logTraceId, 'get products what not published to ebay', 2);

        $baseQuery = Product::query()
            ->where('products.published_to_ebay_de', false)
            ->whereNull('products.ebay_name_de');

        if (!empty($productIds)) {
            $baseQuery->whereIn('products.id', $productIds);
        }

        dump($baseQuery->pluck('id')->toArray());
        dump('product ids');

        // ГЕНЕРАЦИЯ ИМЕНИ ДЛЯ МАШИН (part_of_ebay_name_for_cars)
        $baseQueryForCars = (clone $baseQuery)->whereNull('products.part_of_ebay_name_for_cars');

        $queryFromPlNameToForCars = (clone $baseQueryForCars)
            ->whereNotNull('products.name_original_pl')
            ->where('products.name_original_pl', '!=', '')
            ->orderBy('products.id');
//      ↓ test ↓
        $queryFromPlNameToForCars = (clone $baseQuery)
            ->whereNotNull('products.name_original_pl')
            ->where('products.name_original_pl', '!=', '')
            ->orderBy('products.id');
//      ↑ test ↑

        $queryFromEBayToForCars = (clone $baseQueryForCars)
            ->with(['ebaySimilarProducts'])
            ->where(function ($query) {
                $query->whereNull('products.name_original_pl')
                    ->orWhere('products.name_original_pl', '');
            })
            ->has('ebaySimilarProducts')
            ->orderBy('products.id');
//      ↓ test ↓
//        $queryFromEBayToForCars = (clone $baseQueryForCars)
//            ->with(['ebaySimilarProducts'])
//            ->has('ebaySimilarProducts')
//            ->orderBy('products.id');
//      ↑ test ↑

        $queryFromTecdocToForCars = (clone $baseQueryForCars)
            ->with(['productCompatibilities'])
            ->where(function ($query) {
                $query->whereNull('products.name_original_pl')
                    ->orWhere('products.name_original_pl', '');
            })
            ->doesntHave('ebaySimilarProducts')
            ->has('productCompatibilities')
            ->orderBy('products.id');
//      ↓ test ↓
//        $queryFromTecdocToForCars = (clone $baseQueryForCars)
//            ->with(['productCompatibilities'])
//            ->has('productCompatibilities')
//            ->orderBy('products.id');
//      ↑ test ↑

        // ОПРЕДЕЛЕНИЕ ТИПА ТОВАРА (part_of_ebay_de_name_product_type)
        $baseQueryType = (clone $baseQuery)->whereNull('products.part_of_ebay_de_name_product_type');

        $queryFromEBayToType = (clone $baseQueryType)
            ->with(['ebaySimilarProducts'])
            ->has('ebaySimilarProducts')
            ->orderBy('products.id');

        $queryFromTecdocToType = (clone $baseQueryType)
            ->whereNotNull('products.product_type_de')
            ->where('products.product_type_de', '!=', '')
            ->doesntHave('ebaySimilarProducts')
            ->orderBy('products.id');
//      ↓ test ↓
//        $queryFromTecdocToType = (clone $baseQueryType)
//            ->whereNotNull('products.product_type_de')
//            ->where('products.product_type_de', '!=', '')
//            ->orderBy('products.id');
//      ↑ test ↑

        $countOfProducts = $baseQuery->count();
        Log::add($logTraceId, 'got' . $countOfProducts . ' products for preparing names by AI', 2);
        dump('got' . $countOfProducts . ' products for preparing names by AI');
        $aiClass = app()->make(ArtificialIntelligenceService::class, [
            'logTraceId' => $logTraceId
        ]);

        $productUpdateFields = [
            'part_of_ebay_name_for_cars',
            'part_of_ebay_de_name_product_type',
            'ebay_name_de',
        ];
        $productUpdateData = [];

        dump($queryFromPlNameToForCars->count());
        dump('$queryFromPlNameToForCars->count()');
        dump($queryFromEBayToForCars->count());
        dump('$queryFromEBayToForCars->count()');
        dump($queryFromTecdocToForCars->count());
        dump('$queryFromTecdocToForCars->count()');
        dump($queryFromEBayToType->count());
        dump('$queryFromEBayToType->count()');
        dump($queryFromTecdocToType->count());
        dump('$queryFromTecdocToType->count()');

        $this->runChunkGeneratingEbayNames(
            $queryFromPlNameToForCars,
            fn($p) => $p->name_original_pl,
            $aiClass->formatPolandNameForCars(...),
            'Generating eBay part Name (for Cars) from Gemini by PL Name',
            $logTraceId,
            $productUpdateData,
            chunkSize: 10
        );

        $this->runChunkGeneratingEbayNames(
            $queryFromEBayToForCars,
            $this->eBayNamesImploding(...),
            $aiClass->generateCarCompatibilityFromEbayDeNames(...),
            'Generating eBay part Name (for Cars) from Gemini by eBay DE Names',
            $logTraceId,
            $productUpdateData,
            chunkSize: 5
        );

        $this->runChunkGeneratingEbayNames(
            $queryFromTecdocToForCars,
            function($p) {
                $productCompatibilitiesIds = $p->productCompatibilities
                    ->pluck('car_tecdoc_id')
                    ->toArray();
                $productCompatibilitiesArray = EbayData::setCompatibiliesToXML($productCompatibilitiesIds);
                $productCompatibilities = $this->prepareCompatibilitiesStringForGemini($productCompatibilitiesArray);
                return $productCompatibilities;
            },
            $aiClass->generateCarCompatibilityFromTecDoc(...),
            'Generating eBay part Name (for Cars) from Gemini by TecDoc',
            $logTraceId,
            $productUpdateData,
            chunkSize: 10
        );

        $this->runChunkGeneratingEbayNames(
            $queryFromEBayToType,
            $this->eBayNamesImploding(...),
            $aiClass->generateProductTypeFromEbayDeNames(...),
            'Generating eBay part Name (Product Type) from Gemini by eBay DE Names',
            $logTraceId,
            $productUpdateData,
            'part_of_ebay_de_name_product_type',
            chunkSize: 5
        );

        $this->runChunkGeneratingEbayNames(
            $queryFromTecdocToType,
            function($p) {
                return "product type: \"{$p->product_type_de}\", installation position: \"{$p->installation_position_de}\", specifics: \"{$p->specifics_de}\"";
            },
            $aiClass->generateProductTypeFromTecdoc(...),
            'Generating eBay part Name (Product Type) from Gemini by TecDoc',
            $logTraceId,
            $productUpdateData,
            'part_of_ebay_de_name_product_type',
            chunkSize: 10
        );

        $toShorten = [];

        foreach ($productUpdateData as $key => $item) {
            if($item['part_of_ebay_de_name_product_type'] and $item['part_of_ebay_name_for_cars']) {
                $fullName = $item['part_of_ebay_de_name_product_type'] . ' ' . $item['part_of_ebay_name_for_cars'];

                if (mb_strlen($fullName) > 80) {
                    // Добавляем в список на сокращение
                    $toShorten[$key] = $fullName;
                } else {
                    // Сразу формируем финал для тех, кто прошел по лимиту
                    $productUpdateData[$key]['ebay_name_de'] = $fullName;
                }
            }
        }

        dump($toShorten);

        $this->shortenEbayNamesByArtificial(
            $aiClass->generateShortenEbayName(...),
            $toShorten,
            $productUpdateData,
            $logTraceId,
            chunkSize: 10
        );

        dump($productUpdateData);

        if (!empty($productUpdateData)) {
            $now = now();
            foreach ($productUpdateData as $key => $item) {
                foreach ($productUpdateFields as $field) {
                    // Проверяем: если значение есть и оно не пустая строка — берем его.
                    // Иначе — записываем null.
                    $val = $item[$field] ?? null;
                    $productUpdateData[$key][$field] = ($val !== '') ? $val : null;
                }

                $productUpdateData[$key]['updated_at'] = $now;
            }

            $productUpdateFields[] = 'updated_at';
            Product::upsert($productUpdateData, ['id'], $productUpdateFields);
        }

        Log::add($logTraceId, 'update db by artificial data', 3);
        Log::add($logTraceId, 'finish work fromGemini', 1);

        return true;
    }

    /**
     * Универсальный метод для генерации названия через Gemini.
     */
    protected function generateEbayNameFromGemini(
        \Illuminate\Support\Collection $products,
        callable $textGenerator,
        callable $geminiCaller,
        string | null $logTraceId,
        int $chunkKey,
        string $fieldName = 'part_of_ebay_name_for_cars'
    ): void {
        if ($products->isEmpty()) {
            return;
        }

        // 1. Формируем текст с помощью переданного callback
        $inputText = "";
        foreach ($products as $idx => $product) {
            $inputText .= ($idx + 1) . ")\n" . $textGenerator($product) . "\n";
        }

//        dump($inputText);

        try {
            // 2. Вызываем нужный метод Gemini
            $parsedNames = $geminiCaller($inputText, $products->count());

            if (count($parsedNames) === $products->count()) {
                // 3. Сопоставляем результаты
                foreach ($products as $idx => $product) {
                    $product->{$fieldName} = $parsedNames[$idx];
                }
            } else {
                dump("AI Error: Count mismatch or not an array");
                Log::add($logTraceId, "AI Error: Count mismatch or not an array for chunk {$chunkKey}", 4);
            }

//            dump($parsedNames);
        } catch (\Exception $e) {
            Log::add($logTraceId, "Artifical Exception: " . $e->getMessage(), 4);
        }
    }

    /**
     * Алгоритмическая группировка данных TecDoc совместимости для Gemini
     */
    private function prepareCompatibilitiesStringForGemini(array $compatibilityArray): string
    {
        if (empty($compatibilityArray)) return "No data";

        $grouped = [];

        foreach ($compatibilityArray as $item) {
            $list = $item['Compatibility']['NameValueList'] ?? [];
            $d = [];
            foreach ($list as $pair) { $d[$pair['Name']] = $pair['Value']; }

            if (!isset($d['Make'], $d['Model'])) continue;

            $make = $d['Make'];
            $trim = $d['Trim'] ?? '';

            // Года
            preg_match_all('/\b\d{4}\b/', $d['Year'] ?? '', $matches);
            $years = $matches[0] ?? [];

            // --- НОВЫЙ БЛОК: Разделение платформ ---
            // "F10, F18" превращается в массив ["F10", "F18"]
            $platforms = isset($d['Platform'])
                ? array_map('trim', explode(',', $d['Platform']))
                : ['Standard']; // Если платформы нет

            foreach ($platforms as $platform) {
                $groupKey = "{$d['Model']} {$platform}";

                if (!isset($grouped[$make][$groupKey])) {
                    $grouped[$make][$groupKey] = ['engines' => [], 'min_year' => null, 'max_year' => null];
                }

                if ($trim) {
                    $grouped[$make][$groupKey]['engines'][$trim] = true;
                }

                foreach ($years as $year) {
                    $year = (int)$year;
                    if (is_null($grouped[$make][$groupKey]['min_year']) || $year < $grouped[$make][$groupKey]['min_year'])
                        $grouped[$make][$groupKey]['min_year'] = $year;
                    if (is_null($grouped[$make][$groupKey]['max_year']) || $year > $grouped[$make][$groupKey]['max_year'])
                        $grouped[$make][$groupKey]['max_year'] = $year;
                }
            }
        }

        $outputLines = [];
        $makeCount = 0;
        foreach ($grouped as $make => $models) {
            if ($makeCount >= 10) break;

            $modelCount = 0;
            foreach ($models as $modelName => $data) {
                // 2. Лимит на количество СТРОК (моделей/платформ) внутри марки - не более 5
                if ($modelCount >= 5) break;

                // 3. Лимит на количество ДВИГАТЕЛЕЙ внутри группы - до 20 штук
                $enginesArray = array_keys($data['engines']);
                if (count($enginesArray) > 20) {
                    $engines = implode(', ', array_slice($enginesArray, 0, 20)) . '...';
                } else {
                    $engines = implode(', ', $enginesArray);
                }

                $years = ($data['min_year'] && $data['max_year'])
                    ? "({$data['min_year']}-{$data['max_year']})"
                    : "";

                // Формируем красивую строку
                $outputLines[] = "{$make} {$modelName} [{$engines}] {$years}";

                $modelCount++;
            }

            $makeCount++;
        }

        return implode("\n", $outputLines);
    }

    private function runChunkGeneratingEbayNames(
        $query,
        $dataCollectorMethod,
        $geminiMethod,
        $logMessage,
        $logTraceId,
        &$productUpdateData,
        $fieldName = 'part_of_ebay_name_for_cars',
        $chunkSize = 5
    ): void
    {
        $chunkKey = 1;
        $query->chunk($chunkSize, function ($products) use ($logTraceId, $dataCollectorMethod, $geminiMethod, $logMessage, &$chunkKey, &$productUpdateData, $fieldName) {
            Log::add($logTraceId, 'start chunk ' . $chunkKey . " {$logMessage}", 2);

            $this->generateEbayNameFromGemini(
                $products,
                $dataCollectorMethod,
                $geminiMethod,
                $logTraceId,
                $chunkKey,
                $fieldName
            );

            foreach ($products as $product) {
                $productUpdateData[$product->id]['id'] ??= $product->id;

                // Записываем результат работы Gemini в массив
                $productUpdateData[$product->id][$fieldName] = $product->{$fieldName};

                // Подтягиваем значения других полей, если они уже есть в объекте, но еще не в массиве
                $productUpdateData[$product->id]['part_of_ebay_name_for_cars'] ??= $product->part_of_ebay_name_for_cars;
                $productUpdateData[$product->id]['part_of_ebay_de_name_product_type'] ??= $product->part_of_ebay_de_name_product_type;
            }

            $chunkKey = $chunkKey + 1;
            Log::add($logTraceId, "finish chunk {$logMessage}", 3);
        });
    }

    private function eBayNamesImploding($p): string
    {
        $namesStr = "";
        foreach ($p->ebaySimilarProducts as $similar) {
            $names = json_decode($similar->names, true);
            if (is_array($names)) {
                $namesStr .= implode("\n", array_slice(array_unique($names), 0, 10)) . "\n";
            }
        }
        return $namesStr;
    }

    public function shortenEbayNamesByArtificial($aiCaller, array $items, &$productUpdateData, $logTraceId, $chunkSize = 10): void
    {
        if (empty($items)) {
            dump('empty shorten array');
            return;
        }

        // Разделяем входящие товары на чанки по 10 штук
        // preserve_keys = true критически важен для сохранения оригинальных индексов
        $chunks = array_chunk($items, $chunkSize, true);

        foreach ($chunks as $chunkKey => $chunkItems) {
            // 1. Формируем текстовый список для промпта
            $inputText = "";
            $index = 1;
            $keysMap = []; // Храним карту соответствия: Порядковый номер => Оригинальный ключ

            foreach ($chunkItems as $key => $fullName) {
                $inputText .= "{$index}) {$fullName}\n";
                $keysMap[$index] = $key;
                $index++;
            }

            $currentItemsCount = count($chunkItems);

            try {
                // 2. Вызываем нужный метод Artificial Class
                $parsedNames = $aiCaller($inputText, $currentItemsCount);

                if (count($parsedNames) === $currentItemsCount) {
                    // 5. Сопоставляем обратно с оригинальными ключами
                    foreach ($parsedNames as $i => $shortenedValue) {
                        $originalIndex = $i + 1; // Так как ИИ считает с 1
                        if (isset($keysMap[$originalIndex])) {
                            if (mb_strlen($shortenedValue) <= 80) {
                                $productUpdateData[$keysMap[$originalIndex]]['ebay_name_de'] = $shortenedValue;
                            } else {
                                // Жестко режем до 80 и убираем лишние пробелы/запятые в конце
                                $hardCut = mb_substr($shortenedValue, 0, 80);

                                // Опционально: откатываемся до последнего пробела,
                                // чтобы не резать слово "MITSUBI..." на "MITSUB"
                                $lastSpace = mb_strrpos($hardCut, ' ');
                                if ($lastSpace !== false) {
                                    $hardCut = mb_substr($hardCut, 0, $lastSpace);
                                }

                                $productUpdateData[$keysMap[$originalIndex]]['ebay_name_de'] = trim($hardCut);
                            }
                        }
                    }
                } else {
                    dump("AI Error: Count mismatch or not an array");
                    Log::add($logTraceId, "AI Error: Count mismatch or not an array for chunk {$chunkKey}", 4);
                }
            } catch (\Exception $e) {
                Log::add($logTraceId, "Artifical Exception: " . $e->getMessage(), 4);
            }
        }
    }
}
