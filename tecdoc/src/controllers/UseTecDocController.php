<?php

namespace Great\Tecdoc\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UseTecDocController
{
    function getProductInfo($reference, $brandId) {
        $tecdoc = new TecDocController();
        $info = $tecdoc->getInfoByProductSupplierReference($reference, $brandId);

        return $info;
    }

    function getProductsInfo(Request $request) {
        $products = $request->getContent();
        $products = json_decode($products, true);

        $data = [];

        foreach ($products as $product) {
            $item = $this->getProductInfo($product['reference'], $product['brand_id']);
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