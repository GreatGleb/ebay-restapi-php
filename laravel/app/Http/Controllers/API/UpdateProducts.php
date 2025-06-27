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
            if (!$product['supplier']) {
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
        Log::add($logTraceId, 'start work', 1);
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
            $response = Http::timeout(300)
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

        Log::add($logTraceId, 'finish work', 1);

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

            $result = false;
        } else {
            Log::add($logTraceId, 'response: ' . $response->body(), 2);
        }

        Log::add($logTraceId, 'finish updating DB to Google Sheets', 1);

        return $result;
    }

    public function fromEbay($logTraceId = null, $productIds = []): bool
    {
        Log::add($logTraceId, 'start work', 1);
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

        Log::add($logTraceId, 'finish work', 1);

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
                'ru_category_from_ebay_de' => $categories[$product['categoryId']],
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
        Log::add($logTraceId, 'start work', 1);
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
            }

            Log::add($logTraceId, 'send request to scrapping', 3);

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
                Log::add($logTraceId, 'update db by tecdoc data', 3);
                $this->updateDbProductTablesFromApNextEu($data, $logTraceId);
            }

            $chunkKey = $chunkKey + 1;

            Log::add($logTraceId, 'finish chunk', 3);
        });

        Log::add($logTraceId, 'finish work', 1);

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
}
