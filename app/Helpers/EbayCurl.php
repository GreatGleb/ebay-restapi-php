<?php

namespace App\Helpers;

use App\Http\Controllers\API\ApiEbayController as Ebay;

class EbayCurl extends Ebay
{
    protected static function sendCurl(Ebay $ebay, $link, $headers, $postFields, $method=true) {
        $ch = curl_init($link);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if($method) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        }
        $response = curl_exec($ch);
        curl_close($ch);

        try {
            $log = simplexml_load_string($response, "SimpleXMLElement", LIBXML_NOCDATA);
        } catch (\Exception $e) {
            $log = json_decode($response, true);
        }

        $ebay->logs[] = $log;

        return $response;
    }

    protected static function getCurlHeaders(Ebay $ebay, $number, $callName = null) {
        $headers = [['Content-Type: application/x-www-form-urlencoded', 'Authorization: Basic '.$ebay->codeAuth],
            ['X-EBAY-API-COMPATIBILITY-LEVEL: ' . 967,
                'X-EBAY-API-DEV-NAME: ' . $ebay->devID,
                'X-EBAY-API-APP-NAME: ' . $ebay->clientID,
                'X-EBAY-API-CERT-NAME: ' . $ebay->secretID,
                'X-EBAY-API-CALL-NAME: ' . $callName,
                'X-EBAY-API-SITEID: ' . $ebay->siteID,
                'X-EBAY-C-MARKETPLACE-ID: ' . $ebay->marketplaceID],
            ['Authorization:Bearer ' . $ebay->access_token, 'Accept:application/json', 'Content-Type:application/json', 'X-EBAY-C-MARKETPLACE-ID: ' . $ebay->marketplaceID, 'X-EBAY-C-ENDUSERCTX: contextualLocation=country%3DUS%2Czip%3D19406']];
        return $headers[$number-1];
    }

    protected static function getCurlPostFields(Ebay $ebay, $field, $item = null) {
        $postFields = [
            "authorization"=> "grant_type=authorization_code&scope=".urlencode($ebay->scopes) . "&code=".$ebay->firstCodeAuth."&redirect_uri=" . $ebay->ruName,
            "refresh"=> "grant_type=refresh_token&refresh_token=".$ebay->refresh_token."&scope=" . urlencode($ebay->scopes),
            "sellerList"=> file_get_contents(public_path() . '\xml\sellerList.xml'),
            "reviseItem"=> file_get_contents(public_path() . '\xml\reviseItem_' . $ebay->marketplaceShortLocale . '.xml'),
            "reviseInventory"=> file_get_contents(public_path() . '\xml\reviseInventoryStatus.xml'),
            "addItem"=> file_get_contents(public_path() . '\xml\addItem_' . $ebay->marketplaceShortLocale . '.xml'),
            "getItem"=> file_get_contents(public_path() . '\xml\getItem.xml'),
            "getCategories"=> file_get_contents(public_path() . '\xml\categories.xml')
        ];
        if($field == 'sellerList') {
            $variables = ['ebay'=>'access_token', 'item'=>['timeFrom', 'timeTo', 'pageNumber']];
            $postFields[$field] = EbayData::addToXMLVariables($postFields[$field], $variables, $item, $ebay);
        } else if($field == 'reviseItem') {
            $variables = ['ebay'=>'access_token', 'item'=>['id', 'updating', 'deliveryMethod']];
            $postFields[$field] = EbayData::addToXMLVariables($postFields[$field], $variables, $item, $ebay);
        } else if($field == 'reviseInventory') {
            $variables = ['ebay'=>'access_token', 'item'=>'inventory'];
            $postFields[$field] = EbayData::addToXMLVariables($postFields[$field], $variables, $item, $ebay);
        } else if($field == 'addItem') {
            $variables = ['ebay'=>'access_token', 'item'=>['adding', 'deliveryMethod']];
            $postFields[$field] = EbayData::addToXMLVariables($postFields[$field], $variables, $item, $ebay);
        } else if($field == 'getItem') {
            $variables = ['ebay'=>'access_token', 'item'=>['id', 'detailLevel']];
            $postFields[$field] = EbayData::addToXMLVariables($postFields[$field], $variables, $item, $ebay);
        } else if($field == 'getCategories') {
            $variables = ['ebay'=>'access_token'];
            $postFields[$field] = EbayData::addToXMLVariables($postFields[$field], $variables, $item, $ebay);
        }

        file_put_contents(__DIR__ . '/ebay_last_request_log.xml', $postFields[$field]);

        return $postFields[$field];
    }
}
