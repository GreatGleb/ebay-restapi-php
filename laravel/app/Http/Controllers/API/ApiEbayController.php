<?php

namespace App\Http\Controllers\API;

use App\Helpers\EbayCurl;
use App\Helpers\EbayData;
use App\Http\Controllers\Controller;
use App\Imports\EbayImport;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('memory_limit', '1024M');

class ApiEbayController extends Controller
{
    protected $siteID = [100, 77][1];
    protected $marketplaceID = ["EBAY_US", "EBAY_DE"][1];
    protected $marketplaceLocale = ["en-US", "de-DE"][1];
    protected $marketplaceShortLocale = ["US", "DE"][1];
    protected $marketplaceSite = ["eBayMotors", "Germany"][1];
    protected $currency = ["USD", "EUR"][1];
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
        return $response;
    }

    public function getReturnPolicies() {
        $headers = EbayCurl::getCurlHeaders($this, 3);
        $url = 'https://api.ebay.com/sell/account/v1/return_policy?marketplace_id=' . $this->marketplaceID;
        $response = EbayCurl::sendCurl($this, $url, $headers, null, false);
        return $response;
    }

    public function getItem($item) {
        $headers = EbayCurl::getCurlHeaders($this, 2, 'GetItem');
        $postFields = EbayCurl::getCurlPostFields($this, 'getItem', $item);

        $response = EbayCurl::sendCurl($this, $this->linkAPI, $headers, $postFields);
        $response = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);

        return $response;
    }

    public function getSellerList() {
        $result = [];

        $result[] = $this->getByDatesSellerList(['2023-03-16', '2023-04-20']);
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

    public function updatingItem($sku, $title, $fields, $nameFields, $itemId=null)
    {
        if(!isset($itemId)) {
            if (!isset($this->items)) {
                $this->getSellerList();
            }

            foreach ($this->items as $item) {
                if (isset($item["SKU"])) {
                    if ($item["SKU"] == $sku and $item["Title"] == $title) {
                        $itemId = $item["ItemID"];
                    }
                }
            }
        }

        $result = '';
        if(isset($itemId)) {
            $item = json_decode('{}');
            $item->id = $itemId;
            $updating = '';

            foreach ($nameFields as $key=>$name) {
                if(isset($fields[$key])) {
                    if ($name == 'deliveryMethod') {
                        $item->deliveryMethod = EbayData::getXMLProperty($fields[$key], $name);
                    } else {
                        $updating .= EbayData::getXMLProperty($fields[$key], $name);
                    }
                }
            }
            $item->updating = $updating;

            $result = $this->reviseItem($item);
        } else {
            $log = json_decode('{}');
            $log->{'Ack'} = 'ItsYetWasnt';
            $this->logs[] = $log;
        }

        return $result;
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

    public function addingItem($fields, $nameFields)
    {
        $item = json_decode('{}');

        $adding = '';

        $titleIndex = array_search('title', $nameFields);
        $skuIndex = array_search('sku', $nameFields);

        foreach ($nameFields as $key=>$name) {
            if(isset($fields[$key])) {
                if($name == 'deliveryMethod') {
                    $item->deliveryMethod = EbayData::getXMLProperty($fields[$key], $name);
                } else {
                    $adding .= EbayData::getXMLProperty($fields[$key], $name);
                }
            }
        }
        $item->adding = $adding;

        $result = $this->addItem($item);
        $result = json_encode($result);
        $result = json_decode($result,TRUE);

        if(isset($result['ItemID'])) {
            $dataForCSV = [$fields[$skuIndex], $fields[$titleIndex], $result['ItemID']];
            $dataForCSV = "\n" . implode('; ', $dataForCSV);
            file_put_contents(__DIR__ . '/ebayItems.csv', $dataForCSV, FILE_APPEND | LOCK_EX);
        }

        return $result;
    }

    public function checkIfItemExists($sku, $title) {
        if(!isset($this->items)) {
            $this->getSellerList();
        }

        $itsExists = 0;

        foreach($this->items as $item) {
            if (isset($item["SKU"])) {
                if ($item["SKU"] == $sku and $item["Title"] == $title) {
                    $itsExists = 1;
                }
            }
        }

        return $itsExists;
    }

    public function updatePostalCodes() {
        $items = [];

        foreach($this->items as $key=>$item) {
            if(is_array($item)) {
                if (isset($item["PostalCode"])) {
                    if ($item["PostalCode"] == '') {
                        $items[$key] = $item;
                        $this->updatingItem($item["SKU"], $item["Title"], ["03202"], ["PostalCode"]);
                    }
                } else {
                    if(isset($item["Title"])) {
                        $items[$key] = $item;
                        $this->updatingItem(null, $item["Title"], ["03202"], ["PostalCode"]);
                    }
                }
            }
        }

        return $items;
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

    public function checkImportLoading(Request $request) {
        $fileNameForChecking = 'loadingDate_' . $request->all()['date'] . '.json';
        $file = @file_get_contents(__DIR__ . '/' . $fileNameForChecking);
        if($file) {
            $fileData = json_decode($file);
            if($fileData[0] == $fileData[1]) {
                unlink(__DIR__ . '/' . $fileNameForChecking);
            }
            return $file;
        } else {
            return '{"earlier": 1}';
        }
    }

    public function importUpdate(Request $request)
    {
        if($request->file instanceof \Illuminate\Http\UploadedFile) {
            $items = (new EbayImport)->toArray($request->file)[0];

            $sizeOfFile = sizeof($items);
            $fileNameForChecking = 'loadingDate_' . $request->all()['date'];
            $dataForChecking = json_encode([$sizeOfFile, 0]);
            file_put_contents(__DIR__ . '/' . $fileNameForChecking . '.json', $dataForChecking);

            $headers = $items[0];

            $allowedFields = ['Artikelnummer', 'Herstellernummer:', 'OE Nummer all', 'DE NAME', 'Category id', 'Kategorie', 'price', 'EAN', 'compatibility', 'aprasymas', 'foto', 'quantity', 'Herstellergarantie:', 'Hersteller:', 'Produkttyp:', 'Country/Region of Manufacture', 'country', 'Country', 'GABARIT', 'Länge mm', 'Einbauposition', 'Vergleichsnummer', 'Keywords', 'New title'];
            $allowedUpdateDefaultFields = [['title'], ['newTitle'], ['description', 'descriptionReplace'], ['compatibility'], ['specifications'], ['pictures'], ['categoryId'], ['price'], ['quantity'], ['deliveryMethod']];
            $allowedUpdateFields = [];
            $replacedFields = ['Artikelnummer' => 'sku', 'Herstellernummer:' => 'nummer', 'OE Nummer all' => 'oe', 'DE NAME' => 'title', 'Category id' => 'categoryId',
                'Kategorie' => 'categoryName', 'EAN' => 'ean', 'aprasymas' => 'description', 'foto' => 'pictures',
                'Herstellergarantie:' => 'garantie', 'Hersteller:' => 'hersteller', 'Produkttyp:' => 'produkttyp',
                'Country/Region of Manufacture' => 'country', 'Country' => 'country',
                'GABARIT'=>'deliveryMethod', 'Länge mm'=>'length', 'Einbauposition'=>'position', 'Vergleichsnummer' => 'vergleichsnummer', 'Keywords'=>'keywords',
                'New title'=>'newTitle'];
            $h = 0;
            foreach ($headers as $header) {
                if (!in_array($header, $allowedFields)) {
                    unset($headers[$h]);
                }
                if (array_key_exists($header, $replacedFields)) {
                    $headers[$h] = $replacedFields[$header];
                }
                $h++;
            }

            $specificationAllNames = ['hersteller', 'produkttyp', 'garantie', 'nummer', 'ean', 'oe', 'country', 'length', 'position', 'keywords'];
            $requestKeys = array_keys($request->all());
            $specificationNames = array_intersect($requestKeys, $specificationAllNames);

            if (isset($request->all()['all']) and $request->all()['all'] == 'on') {
                $allowedUpdateFields = $allowedUpdateDefaultFields;
            } else {
                foreach ($allowedUpdateDefaultFields as $field) {
                    if(in_array($field[0], $requestKeys)) {
                        $allowedUpdateFields[] = $field;
                    }
                }

                if(!in_array('specifications', $requestKeys)) {
                    if (sizeof($specificationNames) > 0) {
                        $allowedUpdateFields[] = ['specifications'];
                    }
                }
            }

            $headersForUpdate = [];
            $h = 0;
            foreach ($headers as $key => $header) {
                foreach ($allowedUpdateFields as $fieldUpdate) {
                    if ($fieldUpdate[0] == $header) {
                        $field = $fieldUpdate[1] ?? $fieldUpdate[0];
                        $headersForUpdate[$key] = $field;
                    }
                    $h++;
                }
            }

            $preparedItems = [];

            $n = 0;
            foreach ($items as $item) {
                if ($n != 0) {
                    $newHeaders = $headers;

                    if (in_array(['specifications'], $allowedUpdateFields)) {
                        $specificationIndex = array_key_last($item) + 1;
                        $item[$specificationIndex] = json_decode('{}');
                        $newHeaders[$specificationIndex] = 'specifications';
                        $headersForUpdate[$specificationIndex] = 'specifications';

                        foreach ($specificationNames as $spec) {
                            $specIndex = array_search($spec, $newHeaders);
                            if ($specIndex === false) {
                                continue;
                            }
                            $item[$specificationIndex]->{$spec} = $item[$specIndex];
                            unset($newHeaders[$specIndex]);
                        }
                    }

                    $descriptionIndex = array_search('description', $newHeaders);
                    if($descriptionIndex !== false) {
                        $descriptionXML = $item[$descriptionIndex];
                        if (in_array(['specifications'], $allowedUpdateFields)) {
                            if(isset($item[$specificationIndex]->oe)) {
                                $item[$descriptionIndex] = EbayData::addDescriptionToHTML($descriptionXML, strlen($item[$specificationIndex]->oe) > 65);
                            }
                        } else {
                            $item[$descriptionIndex] = EbayData::addDescriptionToHTML($descriptionXML, false);
                        }
                    }

                    if(in_array('deliveryMethod', $headersForUpdate)) {
                        $deliveryMethods = $this->getFulfillmentPolicies()['fulfillmentPolicies'];
                        $usual = [];
                        $gabarit = [];
                        $free = [];
                        foreach ($deliveryMethods as $method) {
                            if ($method["fulfillmentPolicyId"] == "######") {
                                $usual = ['id' => $method["fulfillmentPolicyId"], 'name' => $method["name"]];
                            } else if ($method["fulfillmentPolicyId"] == "#####") {
                                $gabarit = ['id' => $method["fulfillmentPolicyId"], 'name' => $method["name"]];
                            } else if ($method["fulfillmentPolicyId"] == "#####") {
                                $free = ['id' => $method["fulfillmentPolicyId"], 'name' => $method["name"]];
                            }
                        }

                        $deliveryMethodIndex = array_search('deliveryMethod', $newHeaders);
                        if ($deliveryMethodIndex !== false) {
                            if ($item[$deliveryMethodIndex] == 'GABARIT') {
                                $item[$deliveryMethodIndex] = $gabarit;
                            } else if ($item[$deliveryMethodIndex] == 'FREE') {
                                $item[$deliveryMethodIndex] = $free;
                            } else {
                                $item[$deliveryMethodIndex] = $usual;
                            }
                        } else {
                            $deliveryMethodIndex = array_key_last($item) + 1;
                            $item[$deliveryMethodIndex] = $usual;
                            $newHeaders[$deliveryMethodIndex] = 'deliveryMethod';
                        }
                    }

                    $titleIndex = array_search('title', $newHeaders);
                    $item['title'] = $item[$titleIndex];
                    $newTitleIndex = array_search('newTitle', $newHeaders);
                    if($newTitleIndex !== false) {
                        if(isset($item[$newTitleIndex])) {
                            $item[$titleIndex] = $item[$newTitleIndex];
                        }
                    }

                    $item['headers'] = $headersForUpdate;

                    $preparedItems[] = $item;
                }
                EbayData::setImportCheckResult($fileNameForChecking, $sizeOfFile);
                $n++;
            }

            return json_encode($preparedItems);
        } else if(is_string($request->file)) {
            $preparedItems = json_decode($request->file, true);
            $sizeOfFile = sizeof($preparedItems);
            $fileNameForChecking = 'loadingDate_' . $request->all()['date'];
            $dataForChecking = json_encode([$sizeOfFile, 0]);
            file_put_contents(__DIR__ . '/' . $fileNameForChecking . '.json', $dataForChecking);

            $fileNameForResult = 'loadingResults_' . $request->all()['date'];
            $fileNameForLogs = 'loadingLogs_' . $request->all()['date'];

            $n = 0;
            foreach ($preparedItems as $preparedItem) {
                $specIndex = array_search('specifications', $preparedItem['headers']);
                if ($specIndex !== false) {
                    $spec = json_decode('{}');
                    foreach ($preparedItem[$specIndex] as $key=>$prop) {
                        $spec->{$key} = $prop;
                    }
                    $preparedItem[$specIndex] = $spec;
                }
                $sku = $preparedItem[0];
                $skuIndex = array_search('sku', $preparedItem['headers']);
                if ($skuIndex !== false) {
                    $sku = $preparedItem[$skuIndex];
                }
                $num = count($this->logs);
                $i = 0;
                while (count($this->logs) == $num) {
                    if ($i == 0) {
                        $this->updatingItem($sku, $preparedItem['title'], $preparedItem, $preparedItem['headers']);
                    }
                    $i++;
                }
                EbayData::setImportResults($fileNameForChecking, $sizeOfFile, $sku, $preparedItem['title'], $this->logs, $n, $fileNameForResult, $fileNameForLogs);
                $n++;
            }

            $emptyStatus = 'ItsYetWasnt';
            $result = EbayData::getImportResults($emptyStatus, $fileNameForChecking, $fileNameForResult, $fileNameForLogs);
            return $result;
        }
    }

    public function importAdd(Request $request)
    {
        $emptyStatus = 'ItsAlreadyWas';
        if($request->file instanceof \Illuminate\Http\UploadedFile) {
            $items = (new EbayImport)->toArray($request->file)[0];

            $sizeOfFile = sizeof($items) - 1;
            $fileNameForChecking = 'loadingDate_' . $request->all()['date'];
            $dataForChecking = json_encode([$sizeOfFile, 0]);
            file_put_contents(__DIR__ . '/' . $fileNameForChecking . '.json', $dataForChecking);
            $fileNameForResult = 'loadingResults_' . $request->all()['date'];
            $fileNameForLogs = 'loadingLogs_' . $request->all()['date'];

            $headers = $items[0];

            $allowedFields = ['Artikelnummer', 'Herstellernummer:', 'OE Nummer all', 'DE NAME', 'Category id', 'Kategorie', 'price', 'EAN', 'compatibility', 'aprasymas', 'foto', 'quantity', 'Herstellergarantie:', 'Hersteller:', 'Produkttyp:', 'Country/Region of Manufacture', 'country', 'Country', 'GABARIT', 'Länge mm', 'Einbauposition', 'Vergleichsnummer', 'Keywords'];
            $replacedFields = ['Artikelnummer' => 'sku', 'Herstellernummer:' => 'nummer', 'OE Nummer all' => 'oe', 'DE NAME' => 'title', 'Category id' => 'categoryId',
                'Kategorie' => 'categoryName', 'EAN' => 'ean', 'aprasymas' => 'description', 'foto' => 'pictures',
                'Herstellergarantie:' => 'garantie', 'Hersteller:' => 'hersteller', 'Produkttyp:' => 'produkttyp',
                'Country/Region of Manufacture' => 'country', 'Country' => 'country', 'GABARIT'=>'deliveryMethod',
                'Länge mm'=>'length', 'Einbauposition'=>'position', 'Vergleichsnummer' => 'vergleichsnummer', 'Keywords'=>'keywords'];
            $h = 0;
            foreach ($headers as $header) {
                if (!in_array($header, $allowedFields)) {
                    unset($headers[$h]);
                }
                if (array_key_exists($header, $replacedFields)) {
                    $headers[$h] = $replacedFields[$header];
                }
                $h++;
            }

            $preparedItems = [];

            $n = 0;
            foreach ($items as $itemXLS) {
                if ($n != 0) {
                    $titleIndex = array_search('title', $headers);
                    $title = $itemXLS[$titleIndex];
                    if ($this->checkIfItemExists($itemXLS[0], $title)) {
                        EbayData::setImportCheckResult($fileNameForChecking, $sizeOfFile);

                        $ack = 'ItsAlreadyWas';
                        EbayData::setImportResult($fileNameForResult, $ack);

                        $log = json_decode('{}');
                        $log->{'Ack'} = $ack;
                        $log->{'SKU'} = $itemXLS[0];
                        $log->{'Title'} = $title;
                        $log->{'numberInFile'} = $n + 1;

                        EbayData::setImportLogResult($fileNameForLogs, $log);

                        $n++;
                        continue;
                    }

                    $newHeaders = $headers;

                    $categoryIdIndex = array_search('categoryId', $newHeaders);
                    $categoryNameIndex = array_search('categoryName', $newHeaders);
                    $categoryIndex = array_key_last($itemXLS) + 1;
                    $itemXLS[$categoryIndex] = [$itemXLS[$categoryIdIndex], $itemXLS[$categoryNameIndex]];
                    $newHeaders[$categoryIndex] = 'category';
                    unset($newHeaders[$categoryIdIndex]);
                    unset($newHeaders[$categoryNameIndex]);

                    $currencyIndex = array_key_last($itemXLS) + 1;
                    $itemXLS[$currencyIndex] = $this->currency;
                    $newHeaders[$currencyIndex] = 'currency';

                    $deliveryMethods = $this->getFulfillmentPolicies()['fulfillmentPolicies'];
                    $usual = []; $gabarit = []; $free = [];
                    foreach ($deliveryMethods as $method) {
                        if($method["fulfillmentPolicyId"] == "#####") {
                            $usual = ['id'=>$method["fulfillmentPolicyId"], 'name'=>$method["name"]];
                        } else if($method["fulfillmentPolicyId"] == "#####") {
                            $gabarit = ['id'=>$method["fulfillmentPolicyId"], 'name'=>$method["name"]];
                        } else if($method["fulfillmentPolicyId"] == "####") {
                            $free = ['id'=>$method["fulfillmentPolicyId"], 'name'=>$method["name"]];
                        }
                    }

                    $deliveryMethodIndex = array_search('deliveryMethod', $newHeaders);
                    if ($deliveryMethodIndex !== false) {
                        if($itemXLS[$deliveryMethodIndex] == 'GABARIT') {
                            $itemXLS[$deliveryMethodIndex] = $gabarit;
                        } else if($itemXLS[$deliveryMethodIndex] == 'FREE') {
                            $itemXLS[$deliveryMethodIndex] = $free;
                        } else {
                            $itemXLS[$deliveryMethodIndex] = $usual;
                        }
                    } else {
                        $deliveryMethodIndex = array_key_last($itemXLS) + 1;
                        $itemXLS[$deliveryMethodIndex] = $usual;
                        $newHeaders[$deliveryMethodIndex] = 'deliveryMethod';
                    }

                    $siteIndex = array_key_last($itemXLS) + 1;
                    $itemXLS[$siteIndex] = $this->marketplaceSite;
                    $newHeaders[$siteIndex] = 'site';

                    $specificationIndex = array_key_last($itemXLS) + 1;
                    $itemXLS[$specificationIndex] = json_decode('{}');
                    $newHeaders[$specificationIndex] = 'specifications';
                    $specificationNames = ['hersteller', 'produkttyp', 'garantie', 'nummer', 'ean', 'oe', 'country', 'length', 'position', 'keywords'];
                    foreach ($specificationNames as $spec) {
                        $specIndex = array_search($spec, $newHeaders);
                        if ($specIndex !== false) {
                            $itemXLS[$specificationIndex]->{$spec} = $itemXLS[$specIndex];
                            unset($newHeaders[$specIndex]);
                        }
                    }

                    $descriptionIndex = array_search('description', $newHeaders);
                    $descriptionXML = $itemXLS[$descriptionIndex];
                    $itemXLS[$descriptionIndex] = EbayData::addDescriptionToHTML($descriptionXML, strlen($itemXLS[$specificationIndex]->oe) > 65);

                    $sku = $itemXLS[0];
                    $skuIndex = array_search('sku', $newHeaders);
                    if ($skuIndex !== false) {
                        $sku = $itemXLS[$skuIndex];
                    }

                    $itemXLS['title'] = $title;
                    $itemXLS['headers'] = $newHeaders;

                    $preparedItems[] = $itemXLS;

                    EbayData::setImportResults($fileNameForChecking, $sizeOfFile, $sku, $itemXLS[$titleIndex], $this->logs, $n, $fileNameForResult, $fileNameForLogs);
                }
                $n++;
            }
            $i = 0;
            $result = 0;
            while(!$result) {
                if($i == 0) {
                    $result = EbayData::getImportResults($emptyStatus, $fileNameForChecking, $fileNameForResult, $fileNameForLogs);
                }
                $i++;
            }

            return json_encode($preparedItems);
        } else if(is_string($request->file)) {
            $preparedItems = json_decode($request->file, true);
            $sizeOfFile = sizeof($preparedItems);

            $fileNameForChecking = 'loadingDate_' . $request->all()['date'];
            $dataForChecking = json_encode([$sizeOfFile, 0]);
            file_put_contents(__DIR__ . '/' . $fileNameForChecking . '.json', $dataForChecking);

            $fileNameForResult = 'loadingResults_' . $request->all()['date'];
            $fileNameForLogs = 'loadingLogs_' . $request->all()['date'];

            $n = 0;
            foreach ($preparedItems as $preparedItem) {
                $specIndex = array_search('specifications', $preparedItem['headers']);
                $specifications = $preparedItem[$specIndex];
                $specificationsObject = json_decode('{}');
                foreach ($specifications as $key=>$spec) {
                    $specificationsObject->{$key} = $spec;
                }
                $preparedItem[$specIndex] = $specificationsObject;

                $num = count($this->logs);
                $i = 0;
                while (count($this->logs) == $num) {
                    if ($i == 0) {
                        $this->addingItem($preparedItem, $preparedItem['headers']);
                    }
                    $i++;
                }

                $sku = $preparedItem[0];
                $skuIndex = array_search('sku', $preparedItem['headers']);
                if ($skuIndex !== false) {
                    $sku = $preparedItem[$skuIndex];
                }
                EbayData::setImportResults($fileNameForChecking, $sizeOfFile, $sku, $preparedItem['title'], $this->logs, $n, $fileNameForResult, $fileNameForLogs);
                $n++;
            }
            $result = EbayData::getImportResults($emptyStatus, $fileNameForChecking, $fileNameForResult, $fileNameForLogs);

            return $result;
        }
    }

    public function index()
    {
//        var_dump($this->setRefreshToken());
//        var_dump($this->getAccessToken());
//        var_dump($this->getCategories());
        $categs = $this->getCategories();
        foreach ($categs as $categ) {
            echo $categ;
            echo "</br>";
        }
//        var_dump($this->getReturnPolicies());
//        echo $this->getAccessToken();
    }
}
