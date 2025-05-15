<?php

namespace Great\Tecdoc\Controllers;

class UseTecDocController
{
    function getProductInfo($reference) {
        $tecdoc = new TecDocController();
        $info = $tecdoc->getInfoByProductSupplierReference($reference, 403);

        return $info;
    }

    function getCarsAndOecodes($reference) {
        $tecdoc = new TecDocController();
        $f = $tecdoc->getCarsAndOecodes($reference, 403);

        return $f;
    }
}