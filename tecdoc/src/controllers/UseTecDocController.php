<?php

namespace Great\Tecdoc\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Great\Tecdoc\Helpers\Log;

class UseTecDocController
{
    function getProductInfo($logTraceId, $reference, $brandId) {
        $tecdoc = new TecDocController($logTraceId);
        $info = $tecdoc->getInfoByProductSupplierReference($reference, $brandId);

        return $info;
    }

    function getAllBrands()
    {
        $tecdoc = new TecDocController(null);
        $brands = $tecdoc->getAllBrands();

        return $brands;
    }

    function testGetProductInfo($reference) {
        $tecdoc = new TecDocController(null);
//        $info = $tecdoc->getAllBrands();
//        $info = $tecdoc->search($reference);
//        $info = $tecdoc->getArticleIdByProductSupplierReference($reference, null);
//        $info = $tecdoc->getInfoByProductSupplierReference($reference, 403);
        $info = $tecdoc->getArticleData($reference, 'ru', true);

        dd($info);

        return $info;
    }

    function getProductsInfo(Request $request) {
        $logTraceId = getallheaders()['Log-Trace-Id'];

        Log::add($logTraceId, 'start work', 4);
        Log::add($logTraceId, 'get request products', 5);

        $products = $request->getContent();
        $products = json_decode($products, true);

        $data = [];

        Log::add($logTraceId, 'start foreach send requests to tecdoc', 5);

        foreach ($products as $key => $product) {
            Log::add($logTraceId, 'foreach product ' . $key, 6);
            $item = $this->getProductInfo($logTraceId, $product['reference'], $product['brand_id']);
            $item["product-id"] = $product["id"];

            $data[] = $item;
        }

        return $data;
    }

    function getCarsAndOecodes($reference, $brandId) {
        $tecdoc = new TecDocController();
        $f = $tecdoc->getCarsAndOecodes($reference, $brandId);

        return $f;
    }
}