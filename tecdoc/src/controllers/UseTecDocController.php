<?php

namespace Great\Tecdoc\Controllers;

class UseTecDocController
{
    function getProductInfo($reference, $brandId) {
        $tecdoc = new TecDocController();
        $info = $tecdoc->getInfoByProductSupplierReference($reference, $brandId);

        return $info;
    }

    function getCarsAndOecodes($reference, $brandId) {
        $tecdoc = new TecDocController();
        $f = $tecdoc->getCarsAndOecodes($reference, $brandId);

        return $f;
    }
}