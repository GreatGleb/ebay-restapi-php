<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Helpers\EbayCurl;
use App\Helpers\EbayData;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ProductController;
use App\Imports\EbayImport;
use App\Models\Product;
use App\Models\ProductCompatibility;
use App\Models\ProductOeCode;
use App\Models\ProductPhoto;

set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '1024M');

class ApiEbayController extends Controller
{
    protected $siteID = [
        'us' => 100,
        'de' => 77,
    ];
    protected $marketplaceID = [
        'us' => 'EBAY_US',
        'de' => 'EBAY_DE',
    ];
    protected $marketplaceLocale = [
        'us' => 'en-US',
        'de' => 'de-DE',
    ];
    protected $marketplaceShortLocale = [
        'us' => 'US',
        'de' => 'DE',
    ];
    protected $marketplaceSite = [
        'us' => "eBayMotors",
        'de' => "Germany",
    ];
    protected $currency = [
        'us' => "USD",
        'de' => "EUR",
    ];
    protected $clientID;
    protected $certID;
    protected $devID;
    protected $secretID;
    protected $ruName;
    protected $codeAuth;
    protected $firstCodeAuth;
    protected $linkFirstAuth;
    protected $linkRefreshAuth = "https://api.ebay.com/identity/v1/oauth2/token";
    protected $linkAPI = "https://api.ebay.com/ws/api.dll";
    protected $scopes = "https://api.ebay.com/oauth/api_scope https://api.ebay.com/oauth/api_scope/sell.marketing.readonly https://api.ebay.com/oauth/api_scope/sell.marketing https://api.ebay.com/oauth/api_scope/sell.inventory.readonly https://api.ebay.com/oauth/api_scope/sell.inventory https://api.ebay.com/oauth/api_scope/sell.account.readonly https://api.ebay.com/oauth/api_scope/sell.account https://api.ebay.com/oauth/api_scope/sell.fulfillment.readonly https://api.ebay.com/oauth/api_scope/sell.fulfillment https://api.ebay.com/oauth/api_scope/sell.analytics.readonly https://api.ebay.com/oauth/api_scope/sell.finances https://api.ebay.com/oauth/api_scope/sell.payment.dispute https://api.ebay.com/oauth/api_scope/commerce.identity.readonly https://api.ebay.com/oauth/api_scope/commerce.notification.subscription https://api.ebay.com/oauth/api_scope/commerce.notification.subscription.readonly";
    protected $refresh_token;
    protected $access_token;
    protected $logs = [];
    protected $items;

    public function __construct()
    {
        $this->clientID = env('EBAY_CLIENT_ID', '');
        $this->firstCodeAuth = env('FIRST_CODE_AUTH', '');
        $this->certID = env('CERT_ID', '');
        $this->devID = env('DEV_ID', '');
        $this->secretID = env('SECRET_ID', '');
        $this->ruName = env('RU_NAME', '');
        $this->codeAuth = base64_encode($this->clientID.':'.$this->certID);

        $this->siteID = $this->siteID['de'];
        $this->marketplaceID = $this->marketplaceID['de'];
        $this->marketplaceLocale = $this->marketplaceLocale['de'];
        $this->marketplaceShortLocale = $this->marketplaceShortLocale['de'];
        $this->marketplaceSite = $this->marketplaceSite['de'];
        $this->currency = $this->currency['de'];

        $this->getAccessToken();
    }

    public function getLinkFirstAuth() {
        $this->linkFirstAuth = "https://auth.ebay.com/oauth2/authorize?client_id=" . $this->clientID;
        $this->linkFirstAuth .= "&response_type=code&redirect_uri=" . $this->ruName;
        $this->linkFirstAuth .= "&scope=" . $this->scopes . "&locale=" . $this->marketplaceLocale;

        return $this->linkFirstAuth;
    }

    private function getRefreshTokens() {
        $headers = EbayCurl::getCurlHeaders($this, 1);
        $postFields = EbayCurl::getCurlPostFields($this, 'authorization');

        $response = EbayCurl::sendCurl($this, $this->linkRefreshAuth, $headers, $postFields);

        $json = json_decode($response, true);

        return $json;
    }

    private function storeRefreshTokens($access_token, $refresh_token) {
        $tokens = [];
        $tokens["access_token"] = $access_token;
        $tokens["refresh_token"] = $refresh_token;
        $result = file_put_contents(__DIR__ . '/tokens.json', json_encode($tokens));

        return $result;
    }

    public function setRefreshToken() {
        $response = $this->getRefreshTokens();
        if(isset($response["access_token"]) && isset($response["refresh_token"])) {
            $access_token = $response["access_token"];
            $refresh_token = $response["refresh_token"];
            $this->access_token = $access_token;
            $this->refresh_token = $refresh_token;
            return $this->storeRefreshTokens($access_token, $refresh_token);
        }
        return $response;
    }

    private function getAccessToken() {
        $headers = EbayCurl::getCurlHeaders($this, 1);

        if(!isset($this->refresh_token)) {
            $json = json_decode(file_get_contents(__DIR__ . '/tokens.json'));
            $this->refresh_token = $json->refresh_token;
        }
        $postFields = EbayCurl::getCurlPostFields($this, 'refresh');

        $response = EbayCurl::sendCurl($this, $this->linkRefreshAuth, $headers, $postFields);
        $json = json_decode($response, true);
        if(isset($json)) {
            if(isset($json["access_token"])) {
                $this->access_token = $json["access_token"];
                $this->storeRefreshTokens($this->access_token, $this->refresh_token);
            }
        }
        return $this->access_token;
    }

    public function getItemData($id)
    {
        $url = 'https://api.ebay.com/buy/browse/v1/item/' . $id;
        $headers = EbayCurl::getCurlHeaders($this, 3);
        $response = EbayCurl::sendCurl($this, $url, $headers, null, false);
        $response = json_decode($response, true);

        return $response;
    }

    public function getItemSpecifics($id)
    {
        $item = $this->getItemData($id);
        $specifics = $item['localizedAspects'] ?? [];

        return $specifics;
    }

    public function getItemsByEAN($ean) {
        $result = [];

        $url = 'https://api.ebay.com/buy/browse/v1/item_summary/search?q=' . $ean . '&limit=10';
        $headers = EbayCurl::getCurlHeaders($this, 3);
        $response = EbayCurl::sendCurl($this, $url, $headers, null, false);
        $response = json_decode($response, true);

        if(isset($response['itemSummaries']) && $response['itemSummaries']) {
            $items = $response['itemSummaries'];

            $result = [
                'names' => [],
                'prices' => [],
            ];

            $bestShopKey = 0;
            $bestShopName = '';

            foreach ($items as $key => $item) {
                if (isset($item['seller']['username']) && $item['seller']['username'] == 'autodoc_shop') {
                    $bestShopKey = $key;
                    $bestShopName = $item['seller']['username'];
                }

                $result['names'][] = $item['title'];
                $result['prices'][] = (float)($item['price']['value'] ?? null);
            }

            if ($bestShopName != 'autodoc_shop') {
                $url = 'https://api.ebay.com/buy/browse/v1/item_summary/search?q=' . $ean . '&limit=100';
                $headers = EbayCurl::getCurlHeaders($this, 3);
                $response = EbayCurl::sendCurl($this, $url, $headers, null, false);
                $response = json_decode($response, true);
                $items = $response['itemSummaries'] ?? [];

                foreach ($items as $key => $item) {
                    if (isset($item['seller']['username']) && $item['seller']['username'] == 'autodoc_shop') {
                        $bestShopKey = $key;
                        $bestShopName = $item['seller']['username'];
                    }
                }
            }

            if ($bestShopName == 'autodoc_shop') {
                if(isset($items[$bestShopKey]['title'])) {
                    $index = array_search($items[$bestShopKey]['title'], $result['names']);

                    if ($index !== false) {
                        unset($result['names'][$index]);
                        array_unshift($result['names'], $items[$bestShopKey]['title']);
                    }
                }
            }

            if (isset($items[$bestShopKey]['title'])) {
                $result['categoryId'] = $items[$bestShopKey]['categories'][0]['categoryId'] ?? null;
                $result['photo'] = $items[$bestShopKey]['image']['imageUrl'] ?? null;
                $result['specifics'] = $this->getItemSpecifics($items[$bestShopKey]['itemId']);
                $result['specifics']['seller'] = $bestShopName;
            }
        } else {
            $result['error'] = true;
        }

        return $result;
    }

    public function searchItemsByProducts($products)
    {
        $data = [];

        foreach ($products as $product) {
            $item = $this->getItemsByEAN($product['ean']);
            $item['product-id'] = $product['id'];

            $data[] = $item;
        }

        return $data;
    }

    public function getFulfillmentPolicies() {
        $headers = EbayCurl::getCurlHeaders($this, 3);
        $url = 'https://api.ebay.com/sell/account/v1/fulfillment_policy?marketplace_id=' . $this->marketplaceID;
        $response = EbayCurl::sendCurl($this, $url, $headers, null, false);
        return json_decode($response, true);
    }

    public function getPaymentPolicies() {
        $headers = EbayCurl::getCurlHeaders($this, 3);
        $url = 'https://api.ebay.com/sell/account/v1/payment_policy?marketplace_id=' . $this->marketplaceID;
        $response = EbayCurl::sendCurl($this, $url, $headers, null, false);
        return json_decode($response, true);
    }

    public function getReturnPolicies() {
        $headers = EbayCurl::getCurlHeaders($this, 3);
        $url = 'https://api.ebay.com/sell/account/v1/return_policy?marketplace_id=' . $this->marketplaceID;
        $response = EbayCurl::sendCurl($this, $url, $headers, null, false);
        return json_decode($response, true);
    }

    public function getAllPolicies()
    {
        $deliveryPolicies = $this->getFulfillmentPolicies()['fulfillmentPolicies'][0] ?? [];
        $deliveryPolicyName = $deliveryPolicies['name'] ?? null;
        $deliveryPolicyId = $deliveryPolicies['fulfillmentPolicyId'] ?? null;

        $returnPolicies = $this->getReturnPolicies()['returnPolicies'][0] ?? [];
        $returnPolicyName = $returnPolicies['name'] ?? null;
        $returnPolicyId = $returnPolicies['returnPolicyId'] ?? null;

        $paymentPolicies = $this->getPaymentPolicies()['paymentPolicies'][0] ?? [];
        $paymentPolicyName = $paymentPolicies['name'] ?? null;
        $paymentPolicyId = $paymentPolicies['paymentPolicyId'] ?? null;

        if(!(
            isset($deliveryPolicyName) and
            isset($deliveryPolicyId) and
            isset($returnPolicyName) and
            isset($returnPolicyId) and
            isset($paymentPolicyName) and
            isset($paymentPolicyId)
        )) {
            return [];
        }

        $sellerProfiles = [
            'SellerShippingProfile' => [
                'ShippingProfileID' => $deliveryPolicyId,
                'ShippingProfileName' => $deliveryPolicyName,
            ],
            'SellerReturnProfile' => [
                'ReturnProfileID' => $returnPolicyId,
                'ReturnProfileName' => $returnPolicyName,
            ],
            'SellerPaymentProfile' => [
                'PaymentProfileID' => $paymentPolicyId,
                'PaymentProfileName' => $paymentPolicyName,
            ]
        ];

        return $sellerProfiles;
    }

    public function prepareXMLtoEbay($logTraceId = null, $type = 'add')
    {
        set_time_limit(0);

        $sellerProfiles = [];
        if($type == 'add') {
            $sellerProfiles = $this->getAllPolicies();
            if(!$sellerProfiles) {
                return false;
            }
        }

//        $start = microtime(true);

        if($type == 'update') {
            $queryProducts = Product::
                where('products.published_to_ebay_de', true)
                ->whereNotNull('products.ebay_de_item_id')
                ->orderBy('products.id');
        } else {
            $queryProducts = Product::
                where('products.published_to_ebay_de', false)
                ->whereNotNull('products.ebay_name_de')
                ->orderBy('products.order_creation_to_ebay_de');
        }

//        $countProducts = $queryProducts->count();

        $chunkKey = 0;

        //, $countProducts, $start
        $queryProducts->chunk(10, function ($products) use ($logTraceId, $sellerProfiles, $type, &$chunkKey) {
//            $end = microtime(true);
//            $executionTime = $end - $start;
//            $start = microtime(true);

            $chunkKey++;

//            if($chunkKey != 1) {
//                return;
//            }

            $productIds = $products->pluck('id');

            $photos = ProductPhoto::
                whereIn('product_id', $productIds)
                ->get()
                ->groupBy('product_id');

            $oeCodes = ProductOeCode::
                whereIn('product_id', $productIds)
                ->get()
                ->groupBy('product_id');

            $ebaySimilarProducts = \DB::table('product_ebay_similar_products')
                ->whereIn('product_id', $productIds)
                ->get()
                ->groupBy('product_id')
                ->toArray();

            foreach ($products as $product) {
//                $exists = $this->checkIfItemExists($product['internal_reference']);
//                if($type == 'add') {
//                    if ($exists) {
//                        continue;
//                    }
//                } else if($type == 'update') {
//                    if (!$exists) {
//                        continue;
//                    }
//                }

                $item = [];

                $item['Country'] = 'LT';
                $item['Location'] = 'Vilnius';
                $item['PostalCode'] = '08214';
                if($type == 'add') {
                    $item['SellerProfiles'] = $sellerProfiles;
                    $item['CategoryMappingAllowed'] = 'true';
                    $item['DispatchTimeMax'] = '1';
                    $item['ListingDuration'] = 'GTC';
                    $item['ListingType'] = 'FixedPriceItem';
                    $item['VATDetails'] = [
                        'BusinessSeller' => 'true',
                        'VATPercent' => '23.0',
                    ];
                    $item['Site'] = $this->marketplaceSite;
                    $item['Currency'] = $this->currency;
                    $item['ConditionID'] = '1000';

                    $itemPhotos = $photos[$product->id]->pluck('cortexparts_photo_url')->toArray() ?? [];
                    if(!$itemPhotos) {
                        continue;
                    }

                    $item['PictureDetails'] = [
                        'PictureURL' => $itemPhotos
                    ];

                    $itemOeCodes = $oeCodes[$product->id]->pluck('number')->toArray() ?? [];
                    $itemOeCodesStr = implode(', ', $itemOeCodes);

                    $ourDefinedSpecifics = [
                        'Hersteller' => $product->producer_brand,
                        'Herstellernummer' => $product->reference,
                        'Herstellergarantie' => '6 Monate',
                        'Oldtimer-Teil' => 'Nein',
                        'Tuning- & Styling-Teil' => 'Nein',
                        'EAN' => $product->ean
                    ];

                    $itemEbaySimilarProducts = $ebaySimilarProducts[$product->id][0] ?? [];
                    if(!$itemEbaySimilarProducts || !$itemEbaySimilarProducts->specifics) {
                        continue;
                    }

                    $itemEbaySimilarProducts = (array) $itemEbaySimilarProducts;
                    $itemSpecificsFromEbaySimilarProducts = $itemEbaySimilarProducts['specifics'];
                    $itemSpecificsFromEbaySimilarProducts = json_decode($itemSpecificsFromEbaySimilarProducts, true);

                    $specificsDict = [];

                    foreach ($itemSpecificsFromEbaySimilarProducts as $itemSpecific) {
                        if(!isset($itemSpecific['name']) or !isset($itemSpecific['value'])) {
                            continue;
                        }

                        $specName = $itemSpecific['name'];
                        if(isset($ourDefinedSpecifics[$specName]) and $ourDefinedSpecifics[$specName]) {
                            $specificsDict[$itemSpecific['name']] = $ourDefinedSpecifics[$specName];
                            continue;
                        }

                        if($specName == 'Einbauposition') {
                            $specPosition = str_replace("Sie die Kompatibilitätsinformationen", '', $itemSpecific['value']);
                            $itemSpecific['value'] = $specPosition;
                        }

                        if($specName == 'Vergleichsnummer') {
                            $itemSpecific['value'] = preg_replace('/autodoc/i', 'CortexParts', $itemSpecific['value']);
                            $itemSpecific['value'] = EbayData::truncateWordsByLimit($itemSpecific['value']);
                        }

                        if($specName == 'OE/OEM Referenznummer(n)') {
                            if($itemSpecific['value'] != $itemOeCodesStr) {
                                $ebayOeCodes = explode(', ', $itemSpecific['value']);

                                $itemSpecific['value'] = implode(', ',
                                    collect($itemOeCodes)
                                        ->merge($ebayOeCodes)
                                        ->unique()
                                        ->values()
                                        ->all());
                            }
                        }

                        $itemSpecific['value'] = trim(preg_replace('/\s+/', ' ', $itemSpecific['value']));
                        $itemSpecific['value'] = mb_substr($itemSpecific['value'], 0, 65);

                        $specName = trim(preg_replace('/\s+/', ' ', $specName));
                        $specName = mb_substr($specName, 0, 65);

                        $specificsDict[$specName] = $itemSpecific['value'];
                    }

                    $specifics = [];
                    foreach ($specificsDict as $name => $value) {
                        $specifics[] = [
                            'Name' => $name,
                            'Value' => $value
                        ];
                    }

                    $item['ItemSpecifics'] = [
                        'NameValueList' => $specifics
                    ];

                    $productCompatibilitiesIds = ProductCompatibility::
                    where('product_id', $product->id)
                        ->get()
                        ->pluck('car_tecdoc_id')
                        ->toArray();

                    $productCompatibilities = EbayData::setCompatibiliesToXML($productCompatibilitiesIds);
                    $item['ItemCompatibilityList'] = $productCompatibilities;
                } else if($type == 'update') {
                    $item['ItemID'] = $product->ebay_de_item_id;
                }

                $item['SKU'] = $product->internal_reference;
                $item['Title'] = $product->ebay_name_de;
                $item['PrimaryCategory'] = [
                    'CategoryID' => $product->category_id_ebay_de
                ];
                $item['StartPrice'] = $product->retail_price_gross;

                $stock = $product->stock_quantity_pruszkow;
                if(!$stock && $product->stock_quantity_pl) {
                    $stock = $product->stock_quantity_pl;
                }

                $item['Quantity'] = $stock;

                $productController = new ProductController();
                $generatedHTML = $productController->getEbayProductHtml($product['id']);
                $item['description'] = $generatedHTML;

                $item = [
                    'Item' => $item
                ];

                $xml = EbayData::arrayToXmlContent($item);

                $dbXMLitem = DB::table('product_prepared_xml_for_upload_to_ebay')
                    ->where('product_id', $product['id'])
                    ->where('type', $type)
                    ->first();

                if ($dbXMLitem) {
                    DB::table('product_prepared_xml_for_upload_to_ebay')
                        ->where('id', $dbXMLitem->id)
                        ->update([
                            'xml' => $xml,
                        ]);
                } else {
                    DB::table('product_prepared_xml_for_upload_to_ebay')->insert([
                        'product_id' => $product['id'],
                        'type' => $type,
                        'xml' => $xml,
                    ]);
                }
            }

//            $end = microtime(true);
//            $executionTime2 = $end - $start;
//            dd($countProducts, 'db:' . $executionTime, 'xml+html:' . $executionTime2);
        });

        return true;
    }

    public function prepareXMLtoAddItems($logTraceId = null)
    {
        return $this->prepareXMLtoEbay($logTraceId);
    }
    public function prepareXMLtoUpdateToEbay($logTraceId = null)
    {
        return $this->prepareXMLtoEbay($logTraceId, 'update');
    }

    public function uploadPreparedItemsToEbay($logTraceId = null, $type = 'add', $productIds = [])
    {
        if($type == 'add') {
            $queryProducts = Product::
                where('products.published_to_ebay_de', false)
                ->whereNotNull('products.ebay_name_de')
                ->whereNotNull('products.order_creation_to_ebay_de');

            if($productIds) {
                $queryProducts = $queryProducts->whereIn('products.id', $productIds);
            }

            $queryProducts = $queryProducts->orderBy('products.order_creation_to_ebay_de');
        } else if($type == 'update') {
            $queryProducts = Product::
                where('products.published_to_ebay_de', true)
                ->whereNotNull('products.ebay_de_item_id')
                ->whereNotNull('products.order_creation_to_ebay_de')
                ->orderBy('products.order_creation_to_ebay_de');
        }

        $chunkKey = 0;

        $results = [];

        $queryProducts->chunk(10, function ($products) use ($logTraceId, &$chunkKey, $type, &$results) {
            $productIds = $products->pluck('id');

            $xmlItems = DB::table('product_prepared_xml_for_upload_to_ebay')
                            ->whereIn('product_id', $productIds)
                            ->where('uploading_at_current_moment', false)
                            ->where('type', $type)
                            ->get()
                            ->toArray();

            foreach ($xmlItems as $xmlItem) {
                $reservedFromOtherProcesses = DB::table('product_prepared_xml_for_upload_to_ebay')
                    ->where('id', $xmlItem->id)
                    ->update([
                        'uploading_at_current_moment' => true,
                    ]);

                if($reservedFromOtherProcesses) {
                    if($type == 'add') {
                        $result = $this->addItem($xmlItem);
                    } else if($type == 'update') {
                        $result = $this->reviseItem($xmlItem);
                    }

                    $result = json_encode($result);
                    $result = json_decode($result,true);

                    $results[] = $result;

                    if($type == 'add') {
                        if (isset($result['ItemID'])) {
                            Product::where('id', $xmlItem->product_id)
                                ->update([
                                    'published_to_ebay_de' => true,
                                    'ebay_de_item_id' => $result['ItemID']
                                ]);
                        }
                    }
                }

                DB::table('product_prepared_xml_for_upload_to_ebay')
                    ->where('id', $xmlItem->id)
                    ->update([
                        'uploading_at_current_moment' => false,
                    ]);
            }

            $chunkKey++;
        });
    }

    public function publicPreparedItemsToEbay($logTraceId = null, $productIds = [])
    {
        $this->uploadPreparedItemsToEbay($logTraceId, $productIds);
    }

    public function updatePreparedItemsToEbay($logTraceId = null)
    {
        $this->uploadPreparedItemsToEbay($logTraceId, 'update');
    }

    //old stuff functions
    public function addItem($item) {
        $headers = EbayCurl::getCurlHeaders($this, 2, 'AddItem');
        $postFields = EbayCurl::getCurlPostFields($this, 'addItem', $item);

        $response = EbayCurl::sendCurl($this, $this->linkAPI, $headers, $postFields);
        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);

        if($xml->Ack == "Failure") {
            if(isset($xml->Errors)) {
                if($xml->Errors->ShortMessage == "Auth token is hard expired.") {
                    $this->getAccessToken();
                    return $this->addItem($item);
                } else {
                    return $xml;
                }
            }
        } else {
            return $xml;
        }
    }

    public function reviseItem($item) {
        $headers = EbayCurl::getCurlHeaders($this, 2, 'ReviseItem');
        $postFields = EbayCurl::getCurlPostFields($this, 'reviseItem', $item);

        $response = EbayCurl::sendCurl($this, $this->linkAPI, $headers, $postFields);
        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);

        if($xml->Ack == "Failure") {
            if(isset($xml->Errors)) {
                if($xml->Errors->ShortMessage == "Auth token is hard expired.") {
                    $this->getAccessToken();
                    return $this->reviseItem($item);
                } else {
                    return $xml;
                }
            }
        } else {
            return $xml;
        }
    }

    public function getCategories() {
        $headers = EbayCurl::getCurlHeaders($this, 2, 'GetCategories');
        $postFields = EbayCurl::getCurlPostFields($this, 'getCategories');

        $response = EbayCurl::sendCurl($this, $this->linkAPI, $headers, $postFields);
        $response = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);

        $categories = [];
        $categoriesArray = $response->CategoryArray->Category ?? [];

        $buildPaths = function(array $categories) {
            $resultsFile = tmpfile();
            $stackFile = tmpfile();

            $childrenLookup = [];
            foreach ($categories as $cat) {
                if ($cat['id'] !== $cat['parent_id']) {
                    $childrenLookup[$cat['parent_id']][] = $cat['id'];
                }
            }

            foreach ($categories as $cat) {
                if ($cat['id'] === $cat['parent_id']) {
                    fwrite($stackFile, serialize([$cat, []]) . "\n");
                }
            }
            rewind($stackFile);

            while (!feof($stackFile)) {
                $line = fgets($stackFile);
                if (!$line) continue;

                [$category, $pathParts] = unserialize(trim($line));

                $newPathParts = [...$pathParts, $category['name']];
                $hasChildren = !empty($childrenLookup[$category['id']]);

                if (!$hasChildren) {
                    $newPathParts[] = $category['id'];
                    fwrite($resultsFile, implode(' → ', $newPathParts) . "\n");
                } else {
                    $tempStack = tmpfile();
                    foreach ($childrenLookup[$category['id']] as $childId) {
                        $child = current(array_filter($categories, fn($c) => $c['id'] == $childId));
                        if ($child) {
                            fwrite($tempStack, serialize([$child, $newPathParts]) . "\n");
                        }
                    }

                    while (!feof($stackFile)) {
                        fwrite($tempStack, fgets($stackFile));
                    }

                    fclose($stackFile);
                    $stackFile = $tempStack;
                    rewind($stackFile);
                }
            }

            rewind($resultsFile);
            $results = [];
            while (!feof($resultsFile)) {
                $line = fgets($resultsFile);
                if ($line !== false) {
                    $results[] = trim($line);
                }
            }

            fclose($resultsFile);
            fclose($stackFile);

            return $results;
        };

        foreach($categoriesArray as $category) {
            if (!isset(
                $category->CategoryID,
                $category->CategoryLevel,
                $category->CategoryName,
                $category->CategoryParentID)
            ) {
                continue;
            }

            $categories[] = [
                'id' => (string)$category->CategoryID,
                'level' => (string)$category->CategoryLevel,
                'name' => (string)$category->CategoryName,
                'parent_id' => (string)$category->CategoryParentID,
            ];
        }

        $categoryPaths = $buildPaths($categories);
        $categoryPathsCarDetails = [];

        foreach($categoryPaths as $categoryPath) {
            $categoryPathArr = explode(' → ', $categoryPath);
            if($categoryPathArr[1] == 'Autoteile & Zubehör') {
                unset($categoryPathArr[0]);
                unset($categoryPathArr[1]);
                $categoryPathsCarDetails[] = implode(' → ', $categoryPathArr);
            }
        }

        return $categoryPathsCarDetails;
    }

    public function getCategoriesText() {
        $categoryPathsCarDetails = $this->getCategories();

        $categoriesText = "";

        foreach ($categoryPathsCarDetails as $categ) {
            $categoriesText .= $categ;
            $categoriesText .= "</br>";
        }

        return $categoriesText;
    }

    public function getCategoryByName($name) {
        $name = urlencode($name);

        $url = 'https://api.ebay.com/commerce/taxonomy/v1/category_tree/' . $this->siteID . '/get_category_suggestions?q=' . $name;
        $headers = EbayCurl::getCurlHeaders($this, 3);
        $response = EbayCurl::sendCurl($this, $url, $headers, null, false);

        return $response;
    }

    public function getItemAspectsForCategory($id) {
        $url = 'https://api.ebay.com/commerce/taxonomy/v1/category_tree/77/get_item_aspects_for_category?category_id=' . $id;
        $headers = EbayCurl::getCurlHeaders($this, 3);
        $response = EbayCurl::sendCurl($this, $url, $headers, null, false);

        $aspects = '';
        $aspects2 = json_decode($response, true)['aspects'];
        foreach ($aspects2 as $aspect) {
            $aspects .= $aspect['localizedAspectName'] . ', ';
        }

        return $aspects;
    }

    public function getRateLimits() {
        $headers = EbayCurl::getCurlHeaders($this, 3);
        $url = 'https://api.ebay.com/developer/analytics/v1_beta/rate_limit?marketplace_id=' . $this->marketplaceID;
        $response = EbayCurl::sendCurl($this, $url, $headers, null, false);
        return $response;
    }

    public function checkIfItemExists($sku) {
        $itsExists = 0;

        if(!isset($this->items)) {
            $this->getSellerList();
        }

        foreach($this->items as $item) {
            if (isset($item["SKU"])) {
                if ($item["SKU"] == $sku) {
                    $itsExists = 1;
                }
            }
        }

        return $itsExists;
    }

    public function getSellerList() {
        $result = [];

        $result[] = $this->getByDatesSellerList(['2025-03-01', '2025-06-08']);

        $result = Arr::collapse($result);

        $this->items = $result;

        return $result;
    }

    private function getByDatesSellerList($dates) {
        $result = [];

        $item = json_decode('{}');
        $item->timeFrom = $dates[0];
        $item->timeTo = $dates[1];
        $item->pageNumber = 0;
        $numberOfPages = 1;
        $i = 0;
        while($i < $numberOfPages) {
            $item->pageNumber = $item->pageNumber + 1;
            $smallResult = $this->getOnePageSellerList($item);
            $smallResult = simplexml_load_string($smallResult, "SimpleXMLElement", LIBXML_NOCDATA);
            if(isset($smallResult->PaginationResult)) {
                if(isset($smallResult->PaginationResult->TotalNumberOfPages)) {
                    $numberOfPages = (int)json_decode(json_encode($smallResult->PaginationResult->TotalNumberOfPages))->{'0'};
                }
            }

            if(isset($smallResult->Ack) and $smallResult->Ack == "Failure") {
                break;
            }

            $smallResult = json_encode($smallResult);
            $smallResult = json_decode($smallResult,TRUE);

            if(isset($smallResult['ItemArray']["Item"])) {
                $smallResult = $smallResult['ItemArray']["Item"];
            } else {
                if(isset($smallResult['ItemArray'])) {
                    $smallResult = $smallResult['ItemArray'];
                }
            }
            $result[] = $smallResult;
            $i++;
        }

        $result = Arr::collapse($result);

        return $result;
    }

    private function getOnePageSellerList($item) {
        $headers = EbayCurl::getCurlHeaders($this, 2, 'GetSellerList');
        $postFields = EbayCurl::getCurlPostFields($this, 'sellerList', $item);

        $response = EbayCurl::sendCurl($this, $this->linkAPI, $headers, $postFields);

        return $response;
    }

    public function exportItems() {
        if(!isset($this->items)) {
            $this->getSellerList();
        }

        $items = [];

        foreach($this->items as $itemObj) {
            if(is_array($itemObj)) {
                if (isset($itemObj['ItemID'])) {
                    $item = json_decode('{}');
                    $item->id = $itemObj['ItemID'];
                    $item->sku = '';
                    if (isset($itemObj['SKU'])) {
                        $item->sku = $itemObj['SKU'];
                    }
                    $item->site = '';
                    if (isset($itemObj['SKU'])) {
                        $item->site = $itemObj['Site'];
                    }
                    $item->shipToLocations = '';
                    if (isset($itemObj['SKU'])) {
                        $item->shipToLocations = $itemObj['ShipToLocations'];
                    }
                    $item->title = '';
                    if (isset($itemObj['Title'])) {
                        $item->title = $itemObj['Title'];
                    }
                    $items[] = $item;
                }
            }
        }

        $items = json_encode($items);
        $path = storage_path('app\public\ebay\\');
        file_put_contents($path . '/ebay_export.json', $items);

        // Download file
        return response()->download($path . '/ebay_export.json', 'ebay_export.json', [
            'Content-Type' => 'application/json',//vnd.ms-excel
            'Content-Disposition' => 'inline; filename="' . 'ebay_export.json' . '"'
        ]);
    }

    public function getItem($item) {
        $headers = EbayCurl::getCurlHeaders($this, 2, 'GetItem');
        $postFields = EbayCurl::getCurlPostFields($this, 'getItem', $item);

        $response = EbayCurl::sendCurl($this, $this->linkAPI, $headers, $postFields);
        $response = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);

        return $response;
    }

    public function reviseInventory($item) {
        $headers = EbayCurl::getCurlHeaders($this, 2, 'ReviseItem');
        $postFields = EbayCurl::getCurlPostFields($this, 'reviseInventory', $item);

        $response = EbayCurl::sendCurl($this, $this->linkAPI, $headers, $postFields);
        $xml = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);

        if($xml->Ack == "Failure") {
            if(isset($xml->Errors)) {
                if($xml->Errors->ShortMessage == "Auth token is hard expired.") {
                    $this->getAccessToken();
                    return $this->reviseInventory($item);
                } else {
                    return $xml;
                }
            }
        } else {
            return $xml;
        }
    }
    public function updatingInventory($items, $nameFields)
    {
        $result = '';
        $inventory = '';

        foreach ($items as $item) {
            $inventory .= '<InventoryStatus>';
            $inventory .= '<ItemID>';
            $inventory .= $item->id;
            $inventory .= '</ItemID>';
            foreach ($nameFields as $key => $name) {
                if (isset($item->{$key})) {
                    $inventory .= EbayData::getXMLProperty($item->{$key}, $name);
                }
            }
            $inventory .= '</InventoryStatus>';
        }

        $item = json_decode('{}');
        $item->inventory = $inventory;
        $result = $this->reviseInventory($item);

        return $result;
    }

    public function updateStockAndPrice() {
        $this->getAccessToken();

        $start = microtime(true);

        $file = public_path() . '\storage\download\OFERTA.xlsx';
        $data = (new EbayImport)->toArray($file)[0];
        if($data[1][0] !== 'Part number') {
            return 'Error: not right file';
        }

        if(!isset($this->items)) {
            $this->getSellerList();
        }

        $dataForResult = [];
        $logs = [];
        $items = [];

        foreach($this->items as $item) {
            if (isset($item["SKU"])) {
                $price = null; $quantity = null;
                foreach ($data as $offer) {
                    if ($offer[0] == $item["SKU"]) {
                        $price = $offer[4];
                        $quantity = $offer[6];
                        break;
                    }
                }
                if($price !== null) {
                    $price = round($price * 1.3 * 1.11 * 1.21);
                }

                if($quantity !== null) {
                    if($quantity == '5>') {
                        if(str_starts_with($item["SKU"], 'EDS')) {
                            $quantity = 10;
                        } else {
                            $quantity = 2;
                        }
                    } else {
                        $quantity = (int)$quantity;
                        if($quantity >= 2) {
                            $quantity = 2;
                        }
                    }
                    if((int)$item['Quantity'] != $quantity or (float)$item['SellingStatus']['CurrentPrice'] != $price) {
                        $updateData = json_decode('{}');
                        $updateData->id = $item['ItemID'];
                        $updateData->{0} = $price;
                        $updateData->{1} = $quantity;
                        $updateData->{2} = $item;
                        $items[] = $updateData;
                    }
                }

                if($price == 0 or $quantity === null) {
                    if($quantity === null) {
                        file_put_contents(__DIR__ . '/ebayLogs.csv', "\n" . date("Y-m-d H:i:s") . ';' . $item["SKU"] . ';notFound', FILE_APPEND | LOCK_EX);
                    } else if($price == 0) {
                        file_put_contents(__DIR__ . '/ebayLogs.csv', "\n" . date("Y-m-d H:i:s") . ';' . $item["SKU"] . ';price: 0', FILE_APPEND | LOCK_EX);
                    }
                }
            }
        }

        $itemChunks = collect($items);
        $itemChunks = $itemChunks->chunk(4);
        $itemChunks = $itemChunks->toArray();

        foreach($itemChunks as $itemsChunk) {
            $num = count($this->logs);
            $i = 0;

            $start_ebay = microtime(true);
            while (count($this->logs) == $num) {
                if ($i == 0) {
                    $headers = ["price", "quantity"];
                    $this->updatingInventory($itemsChunk, $headers);
                }
                $i++;
            }

            $logsIndex = array_key_last($this->logs);
            $logs[] = $this->logs[$logsIndex];
            if(isset($this->logs[$logsIndex]->Ack)) {
                $log = json_decode(json_encode($this->logs[$logsIndex]), TRUE);
                if(isset($dataForResult[$log['Ack']])) {
                    $dataForResult[$log['Ack']] = $dataForResult[$log['Ack']] + 1;
                } else {
                    $dataForResult[$log['Ack']] = 1;
                }
            }
        }

        return $dataForResult;
    }

    public function index()
    {
//        var_dump($this->setRefreshToken());
//        var_dump($this->getAccessToken());
//        var_dump($this->getCategories());
//        var_dump($this->getReturnPolicies());
//        echo $this->getAccessToken();
    }
}
