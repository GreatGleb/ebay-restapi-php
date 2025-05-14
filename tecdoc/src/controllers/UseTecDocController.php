<?php

namespace Great\Tecdoc\Controllers;

use Great\Tecdoc\Controllers\TecDocController;
use Myrzan\TecDocClient\Generated\GetVehicleByIds3;

class UseTecDocController
{
    function getProductInfo($reference) {
        $tecdoc = new TecDocController();
        $name = $tecdoc->getNameByProductSupplierReference($reference, 2);

        return $name;
    }
}