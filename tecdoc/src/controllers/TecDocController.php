<?php

namespace Great\Tecdoc\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Myrzan\TecDocClient\Client;
use Myrzan\TecDocClient\Generated\GetArticleDirectSearchAllNumbersWithState;
use Myrzan\TecDocClient\Generated\GetArticleIdsWithState;
use Myrzan\TecDocClient\Generated\GetArticleLinkedAllLinkingTarget3;
use Myrzan\TecDocClient\Generated\GetArticleLinkedAllLinkingTargetManufacturer;
use Myrzan\TecDocClient\Generated\GetArticles;
use Myrzan\TecDocClient\Generated\GetChildNodesAllLinkingTarget2;
use Myrzan\TecDocClient\Generated\GetDirectArticlesByIds6;
use Myrzan\TecDocClient\Generated\GetModelSeries2;
use Myrzan\TecDocClient\Generated\GetVehicleByIds3;
use Myrzan\TecDocClient\Generated\GetVehicleIdsByCriteria;
use ReflectionClass;
use Great\Tecdoc\Helpers\Log;
use Myrzan\TecDocClient\Generated\GetAmBrands;

class TecDocController
{
    private $apiKey;
    private $providerId;
    private $client;

    public function __construct($logTraceId)
    {
        $this->logTraceId = $logTraceId;
        $this->setApiKeys();
    }

    public function ajaxRequest()
    {
        return view('ajaxRequest');
    }

    public function ajaxCartPost(Request $request)
    {
        $input = $request->all();

        return response()->json(['success'=>'Got Simple Ajax Request.']);
    }

    public function ajaxGetModelSeries2Post(Request $request)
    {
        $params = (new GetModelSeries2())
            ->setCountry('LT')
            ->setLang('LT')
            ->setLinkingTargetType('P')
//            ->setFavouredList(1)
            ->setManuId($request->manuId);
        $response = $this->client->getModelSeries2($params);
        $models = [];
        //$model = $response->getData();
        foreach ($response->getData() as $key => $model)
            $models[$key] = [
                'modelName' => $model->getModelname(),
                'modelId' => $model->getModelId(),
                'years' => $this->data($model),
            ];

        return response()->json($models);
    }

    public function ajaxGetVehicleIdsByCriteriaPost(Request $request)
    {
        $vehicleIdsByCriteria = (new GetVehicleIdsByCriteria())
            ->setCountriesCarSelection('LT')
            ->setLang('LT')
            ->setCarType('P')
            ->setManuId($request->manuId)
            ->setModId($request->modId);

        $vehicleIdsByCriteriaResponse = $this->client->getVehicleIdsByCriteria($vehicleIdsByCriteria);

        $arrayVehicleIdsByCriteria = array_chunk($vehicleIdsByCriteriaResponse->getData(),25);

        $searchCarId = function ($item){
            return $item->getCarId();
        };

        $modification = [];
        $fuels = [];

        foreach ($arrayVehicleIdsByCriteria as $itemCarId){

            $arrayCarId = array_map($searchCarId, $itemCarId);

            $getVehicleByIds3 = (new GetVehicleByIds3())
                ->setCountriesCarSelection('LT')
                ->setArticleCountry('LT')
                ->setLang('LT')
                ->setCountry('LT')
                ->setCarIds($arrayCarId);
            $getVehicleByIds3Response = $this->client->getVehicleByIds3($getVehicleByIds3)->getData();

            foreach ($getVehicleByIds3Response as $itemModification) {

                $mod = $itemModification->getVehicleDetails();
                $fuels[$mod->getCarId()] = $mod->getFuelType();
                $modification[$mod->getCarId()] = [
                    'cylinderCapacityLiter' => strlen($mod->getCylinderCapacityLiter() / 100) !== 1 ? $mod->getCylinderCapacityLiter() / 100 : ($mod->getCylinderCapacityLiter() / 100) . '.0',
                    'powerHpTo' => $mod->getPowerHpTo(),
                    'powerKwTo' => $mod->getPowerKwTo(),
                    'typeName' => $mod->getTypeName(),
                    'fuelType' => $mod->getFuelType(),
                    'years' => $this->data($mod)

                ];
            }
        }
        $fuels = array_unique($fuels);


        return response()->json([
            'fuels' => $fuels,
            'modification' => $modification
        ]);
    }

    public function getMethods(string $class): array {
        if (!class_exists($class)) {
            return [];
        }
        return get_class_methods($class);
    }

    public function getVehicleByIds3($arrayCarId)
    {
        $modification = [];

        $getVehicleByIds3 = (new GetVehicleByIds3())
            ->setCountriesCarSelection('LT')
            ->setArticleCountry('LT')
            ->setLang('LT')
            ->setCountry('LT')
            ->setCarIds($arrayCarId);

        $getVehicleByIds3Response = $this->client->getVehicleByIds3($getVehicleByIds3)->getData();


        $mod = $getVehicleByIds3Response[0]->getVehicleDetails();

        $modification = [
            'cylinderCapacityLiter' => strlen($mod->getCylinderCapacityLiter() / 100) !== 1 ? $mod->getCylinderCapacityLiter() / 100 : ($mod->getCylinderCapacityLiter() / 100) . '.0',
            'manuName' => $mod->getManuName(),
            'modelName' => $mod->getModelName(),
            'carId' => $arrayCarId[0],
            'cylinderCapacityCcm' => $mod->getCylinderCapacityCcm(),
            'constructionType' => $mod->getConstructionType(),
            'powerHpTo' => $mod->getPowerHpTo(),
            'powerKwTo' => $mod->getPowerKwTo(),
            'typeName' => $mod->getTypeName(),
            'fuelType' => $mod->getFuelType(),
            'years' => $this->data($mod)
        ];

        return $modification;
    }

    public function products(Request $request)
    {
        $articles = [];
        $carId = isset(request()->carId) ? request()->carId : $request->carId;
        $category = isset(request()->category) ? request()->category : $request->category;

        $getArticleIdsWithState = (new getArticleIdsWithState())
            ->setArticleCountry('LT')
            ->setLang('LT')
            ->setLinkingTargetId($carId)
            ->setLinkingTargetType('P')
            ->setAssemblyGroupNodeId($category);

        $getVehicleByIds3Response = $this->client->getArticleIdsWithState($getArticleIdsWithState)->getData();

        foreach ($getVehicleByIds3Response as $getItem) {
            array_push($articles, $getItem->getArticleNo());
        }
        $products = Product::whereIn('supplier_reference', $articles)
            ->paginate(15)
            ->appends(request()->query());

        if(request()->sort === 'low_high') {
            $products->setCollection(
                collect(
                    collect($products->items())->sortBy('price')
                )->values()
            );
        } elseif(request()->sort === 'high_low') {
            $products->setCollection(
                collect(
                    collect($products->items())->sortByDesc('price')
                )->values()
            );
        } elseif(request()->sort === 'avail') {

            $productsSort = $products->transform(function($product) {
                if($product->stock_shop + $product->stock_supplier > 0)
                    return $product;
            })->filter(function($value, $key) {
                return !is_null($value);
            });

            $products = new LengthAwarePaginator($productsSort, $productsSort->count(), $products->perPage(), request()->page, [
                'path'  => request()->url(),
                'query' => request()->query(),
            ]);

        } elseif(request()->sort === 'kod') {
            $products->setCollection(
                collect(
                    collect($products->items())->sortBy('reference')
                )->values()
            );
        }

        return view('catalog.tecdoc.products', [
            'products' => $products,
        ]);
    }

    public function tecDocCatalog(Request $request)
    {

        $modification = [];
        if (isset($request->modification)) {
            $modification = $this->getVehicleByIds3([$request->modification]);
        }

        $categories = (new GetChildNodesAllLinkingTarget2())
            ->setArticleCountry('LT')
            ->setLang('LT')
            ->setLinkingTargetType('P')
            ->setLinkingTargetId(isset($request->modification) ? $request->modification: null);

        $getCategories = $this->client->getChildNodesAllLinkingTarget2($categories)->getData();

        return view('catalog.tecdoc.catalog', [
            'categories' => $getCategories,
            'modification' => $modification
        ]);
    }
    public function countProduct($parentId, $carId)
    {
        $productsCat = (new getArticleIdsWithState())
            ->setArticleCountry('LT')
            ->setLang('LT')
            ->setLinkingTargetType('P')
            ->setAssemblyGroupNodeId($parentId)
            ->setLinkingTargetId($carId);

        $productsCatResponse = $this->client->getArticleIdsWithState($productsCat)->getData();
        $articles = [];

        foreach ($productsCatResponse as $productCat) {
            array_push($articles, $productCat->getArticleNo());
        }
        $products = Product::whereIn('supplier_reference', $articles)->count();

        return $products;
    }
    public function getParentCategory(Request $request)
    {
        $categories = (new GetChildNodesAllLinkingTarget2())
            ->setArticleCountry('LT')
            ->setLang('LT')
            ->setLinkingTargetType('P')
            ->setParentNodeId($request->parentId)
            ->setLinkingTargetId($request->carId);

        $getCategories = $this->client->getChildNodesAllLinkingTarget2($categories)->getData();
        $childs = [];

        foreach ($getCategories as $category) {
            array_push($childs,[
                'assemblyGroupName' => $category->getAssemblyGroupName(),
                'assemblyGroupNodeId' => $category->getAssemblyGroupNodeId(),
                'parentNodeId' => $category->getParentNodeId(),
                'hasChilds' => $category->getHasChilds(),
                'count' => $this->countProduct($category->getAssemblyGroupNodeId(), $request->carId)
            ]);
        }

        return response()->json($childs);
    }

    public function data($model)
    {
        return  '('.substr($model->getYearOfConstrFrom(),-2).'.'.
            substr($model->getYearOfConstrFrom(),0,4).' - '.
            substr($model->getYearOfConstrTo(),-2).'.'.
            substr($model->getYearOfConstrTo(),0,4).')';

    }
    public function getCarsAndOecodes($reference, $brandId)
    {
        if(isset($reference) and isset($brandId)) {
            $oeCodes = [];
            $getAtributs = [];

            $getArticleDirectSearchAllNumbersWithState = (new getArticleDirectSearchAllNumbersWithState())
                ->setArticleCountry('LT')
                ->setLang('LT')
                ->setSearchExact(true)
                ->setBrandId($brandId)
                ->setArticleNumber($reference)
                ->setNumberType(0);
            $getArticleDirectSearchAllNumbersWithStateResponse = $this->client->getArticleDirectSearchAllNumbersWithState($getArticleDirectSearchAllNumbersWithState)->getData();

            if(!empty($getArticleDirectSearchAllNumbersWithStateResponse)) {

                $getArticleId = $getArticleDirectSearchAllNumbersWithStateResponse[0]->getArticleId();

                $getDirectArticlesByIds6 = (new getDirectArticlesByIds6())
                    ->setArticleCountry('LT')
                    ->setLang('LT')
                    ->setOeNumbers(true)
                    ->setAttributs(true)
                    ->setArticleId([$getArticleId]);

                $getDirectArticlesByIds6Response = $this->client->getDirectArticlesByIds6($getDirectArticlesByIds6)->getData();

                foreach ($getDirectArticlesByIds6Response[0]->getOenNumbers() as $getOe) {
                    array_push($oeCodes, [
                        'name' => $getOe->getBrandName(),
                        'code' => $getOe->getOeNumber()
                    ]);
                }
                foreach ($getDirectArticlesByIds6Response[0]->getArticleAttributes() as $getAtr) {
                    array_push($getAtributs, [
                        'name' => $getAtr->getAttrName(),
                        'value' => $getAtr->getAttrValue()
                    ]);
                }

                return [
                    'oe' => $oeCodes,
                    'article' => $getArticleId,
                    'info' => $getAtributs,
                    'supplier_reference' => $reference
                ];
            }
        }

        return null;
    }

    public function getArticleCarMarks($articleId)
    {
        $request = (new GetArticleLinkedAllLinkingTargetManufacturer())
            ->setArticleCountry('LT')
            ->setArticleId($articleId)
            ->setLinkingTargetType('P');

        $response = $this->client->getArticleLinkedAllLinkingTargetManufacturer($request);
        $response = $response->getData();

        return $response;
    }

    public function getArticleModelsByCarMark($articleId, $markId)
    {
        $request  = (new GetArticleLinkedAllLinkingTarget3())
            ->setLang('LT')
            ->setArticleCountry('LT')
            ->setArticleId($articleId)
            ->setLinkingTargetManuId($markId)
            ->setLinkingTargetType('P');

        $response = $this->client->getArticleLinkedAllLinkingTarget3($request);
        $response = $response->getData();

        return $response;
    }

    public function getArticleVehicleDetailsByModel($articleId, $carModelId)
    {
        $request = (new GetVehicleByIds3())
            ->setCountriesCarSelection('LT')
            ->setArticleCountry('LT')
            ->setLang('RU')
            ->setCountry('LT')
            ->setCarIds([$carModelId]);

        $response = $this->client->getVehicleByIds3($request);
        $response = $response->getData();

        return $response;
    }

    public function getArticleModels($articleId)
    {
//        $manuArray = [];
        $data = [];

        $carMarks = $this->sendRequest([$this, 'getArticleCarMarks'], $articleId);

        foreach ($carMarks as $key => $manu) {
            $carModels = $this->sendRequest([$this, 'getArticleModelsByCarMark'], $articleId, $manu->getManuId());
            $carModels = $carModels[0]->getArticleLinkages();

//            $manuArray[$key] = [
//                'name' => $manu->getManuName(),
//                'children' => []
//            ];

            foreach ($carModels as $carInfo) {
                $vehicleDetails = $this->sendRequest([$this, 'getArticleVehicleDetailsByModel'], $articleId, $carInfo->getLinkingTargetId());
                $vehicleDetails = $vehicleDetails[0]->getVehicleDetails();

//                if(!empty($vehicleDetails)) {
//                    $data[] = $this->extractObjectProperties($vehicleDetails);
//                }
//                array_push($manuArray[$key]['children'], [
//                    'name' => $mod->getManuName() . ' ' . $mod->getModelName() . ' ' . $mod->getTypeName() . ', ' . $this->data($mod) . ', ' .
//                        $mod->getCylinderCapacityCcm() . ' ccm, ' . $mod->getPowerHpTo() . ' AG, ' . $mod->getPowerKwTo() . ' kW, ' . $mod->getFuelType(),
//                    'carId' => '/vehicle/' . $mod->getCarId()
//                ]);

                $data[] = $vehicleDetails->getCarId();
            }
        }

        return $data;
    }

    /**
     * @throws \ReflectionException
     */
    function extractObjectProperties($object): array {
        $reflection = new ReflectionClass($object);
        $properties = $reflection->getProperties();
        $result = [];

        foreach ($properties as $property) {
            $property->setAccessible(true);
            $result[$property->getName()] = $property->getValue($object);
        }

        return $result;
    }
    function extractArrayOfObjectProperties($arr) {
        if (is_array($arr)) {
            foreach ($arr as $property => $value) {
                if(is_object($value)) {
                    $arr[$property] = $this->extractObjectProperties($value);
                }
            }
        } else if(is_object($arr)) {
            $arr = $this->extractObjectProperties($arr);
        }

        return $arr;
    }

    function deepExtractObjectProperties($object): array {
        $arr = $this->extractObjectProperties($object);

        foreach ($arr as $property => $value) {
            $arr[$property] = $this->extractArrayOfObjectProperties($value);
        }

        return $arr;
    }

    public function getCarsWithoutOecodes(Request $request)
    {
        $product = Product::where('reference', $request->reference)
            ->orWhere('supplier_reference', $request->reference)
            ->first();

        if(isset($request->reference) and isset($product->supplier_reference)) {
            $getArticleDirectSearchAllNumbersWithState = (new getArticleDirectSearchAllNumbersWithState())
                ->setArticleCountry('LT')
                ->setLang('LT')
                ->setSearchExact(true)
                ->setBrandId($product->supplier_brand->tecdoc_id)
                ->setArticleNumber($product->supplier_reference)
                ->setNumberType(0);
            $getArticleDirectSearchAllNumbersWithStateResponse = $this->client->getArticleDirectSearchAllNumbersWithState($getArticleDirectSearchAllNumbersWithState)->getData();

            if(!empty($getArticleDirectSearchAllNumbersWithStateResponse)) {

                $getArticleId = $getArticleDirectSearchAllNumbersWithStateResponse[0]->getArticleId();

                if(isset($request->getArticleId)) {
                    return $getArticleId;
                } else {
                    return response()->json([
                        'article' => $getArticleId
                    ]);
                }
            }
        }
        return null;
    }

    public function getArticleManufacturerWithModels(Request $request)
    {
        $manuArray = [];

        $getArticleLinkedAllLinkingTargetManufacturer = (new GetArticleLinkedAllLinkingTargetManufacturer())
            ->setArticleCountry('LT')
            ->setArticleId($request->article)
            ->setLinkingTargetType('P');

        $getArticleLinkedAllLinkingTargetManufacturerResponse = $this->client->getArticleLinkedAllLinkingTargetManufacturer($getArticleLinkedAllLinkingTargetManufacturer)->getData();
        foreach ($getArticleLinkedAllLinkingTargetManufacturerResponse as $key => $manu) {

            $getArticleLinkedAllLinkingTarget3  = (new GetArticleLinkedAllLinkingTarget3())
                ->setLang('LT')
                ->setArticleCountry('LT')
                ->setArticleId($request->article)
                ->setLinkingTargetManuId($manu->getManuId())
                ->setLinkingTargetType('P');

            $getArticleLinkedAllLinkingTarget3Response = $this->client->getArticleLinkedAllLinkingTarget3($getArticleLinkedAllLinkingTarget3)->getData()[0]->getArticleLinkages();
            $manuArray[$key] = [
                'name' => $manu->getManuId(),
                'models' => []
            ];
            /* array_push($manuArray, [
                 'name' => $manu->getManuName() => '']);*/

            foreach ($getArticleLinkedAllLinkingTarget3Response as $kerCarInfo => $carInfo) {

                $getVehicleByIds3 = (new GetVehicleByIds3())
                    ->setCountriesCarSelection('LT')
                    ->setArticleCountry('LT')
                    ->setLang('LT')
                    ->setCountry('LT')
                    ->setCarIds([$carInfo->getLinkingTargetId()]);

                $getVehicleByIds3Response = $this->client->getVehicleByIds3($getVehicleByIds3)->getData();


                $mod = $getVehicleByIds3Response[0]->getVehicleDetails();

                $manuArray[$key]['models'][$mod->getModId()][$mod->getCarId()] = [
//                    "type" => /*$mod->getManuName().' '.$mod->getModelName().' '.*/$mod->getTypeName().' ('.$mod->getPowerKwTo().' kW / '.$mod->getPowerHpTo().' AG) '.$this->data($mod),/*.', '.
//                            $mod->getCylinderCapacityCcm().' ccm, '.$mod->getFuelType(),*/
                    "type" => $mod->getTypeName(),
                    "yearFrom" => $mod->getYearOfConstrFrom(),
                    "yearTo" => $mod->getYearOfConstrTo(),
                    "cylinderCapacityCcm" => $mod->getCylinderCapacityCcm(),
                    "powerKwTo" => $mod->getPowerKwTo(),
                    "powerHpTo" => $mod->getPowerHpTo(),
                    "fuelType" => $mod->getFuelType()
                ];
            }

        }
        return response()->json($manuArray);
    }
    public function getCars(Request $request)
    {
        if(isset($request->article)) {
            $cars = [];
            $carsCombine = [];

            $getArticleLinkedAllLinkingTarget3 = (new getArticleLinkedAllLinkingTarget3())
                ->setArticleCountry('LT')
                ->setLang('LT')
                ->setLinkingTargetType('P')
                ->setLinkingTargetManuId(183)
                ->setArticleId($request->article);
            $getArticleLinkedAllLinkingTarget3Response = $this->client->getArticleLinkedAllLinkingTarget3($getArticleLinkedAllLinkingTarget3)->getData()[0]->getArticleLinkages();

            foreach ($getArticleLinkedAllLinkingTarget3Response as $getCar) {

                array_push($cars, $this->getVehicleByIds3([$getCar->getLinkingTargetId()]));
            }

            foreach ($cars as $car) {

                $carsCombine += [$car['modelName'] => []];
                array_push($carsCombine[$car['modelName']], $car);
            }

            return response()->json($carsCombine);
        }
        return "null";
    }

    public function search($string)
    {
        $oemCode = $this->getArticleDirectSearchAllNumbersWithState($string, 10);
        if(!is_null($oemCode)) {
            return $oemCode;
        }
        $oemArc = $this->getArticleDirectSearchAllNumbersWithState($string, 0);
        $oemCode = $this->getArticleDirectSearchAllNumbersWithState($string, 1);
        $tradeCode = $this->getArticleDirectSearchAllNumbersWithState($string, 2);
        $comparableCode = $this->getArticleDirectSearchAllNumbersWithState($string, 3);
        $eanCode = $this->getArticleDirectSearchAllNumbersWithState($string, 6);

        if (!is_null($oemArc) && !is_null($oemCode) && !is_null($tradeCode) && !is_null($comparableCode) && !is_null($eanCode))
            return array_merge($oemArc, $oemCode,$comparableCode,$eanCode,$tradeCode);
        return [];
    }
    public function searchOe($string)
    {
        $oemCode = $this->getArticleDirectSearchAllNumbersWithState($string, 1);
        if(!is_null($oemCode)) {
            return $oemCode;
        }
        return [];
    }

    public function getArticleDirectSearchAllNumbersWithState($string, $numberType)
    {
        $getArticleDirectSearchAllNumbersWithState = (new getArticleDirectSearchAllNumbersWithState())
            ->setArticleCountry('LT')
            ->setLang('RU')
            ->setArticleNumber($string)
            //->setSearchExact(true)
            ->setSortType(2)
            ->setNumberType($numberType);

        $getArticleDirectSearchAllNumbersWithStateResponse = $this->client->getArticleDirectSearchAllNumbersWithState($getArticleDirectSearchAllNumbersWithState)->getData();

        return $getArticleDirectSearchAllNumbersWithStateResponse;
    }

    public function getArticleIdByProductSupplierReference($supplier_reference, $brand)
    {
        $articleId = null;

        if(isset($supplier_reference)) {
            $request = (new getArticleDirectSearchAllNumbersWithState())
                ->setArticleCountry('LT')//PL
                ->setLang('en')
                ->setSearchExact(true)
                ->setArticleNumber($supplier_reference)
                ->setNumberType(0);

            if($brand) {
                $request = $request->setBrandId($brand);
            }

            $response = $this->client->getArticleDirectSearchAllNumbersWithState($request)->getData();

            if (!empty($response)) {
                $articleId = $response[0]->getArticleId();
            }
        }

        return $articleId;
    }

    public function getPhotosByProductSupplierReference($supplier_reference, $brand) {
        $images = [];

        $getArticleDirectSearchAllNumbersWithState = (new GetArticleDirectSearchAllNumbersWithState())
            ->setArticleCountry('LT')
            ->setLang('LT')
            ->setSearchExact(true)
            ->setBrandId($brand)
            ->setArticleNumber($supplier_reference)
            ->setNumberType(0);
        $getArticleDirectSearchAllNumbersWithStateResponse = $this->client->getArticleDirectSearchAllNumbersWithState($getArticleDirectSearchAllNumbersWithState)->getData();

        $articleId = count($getArticleDirectSearchAllNumbersWithStateResponse) ? $getArticleDirectSearchAllNumbersWithStateResponse[0]->getArticleId() : null;

        if($articleId) {
            $getDirectArticlesByIds6 = (new getDirectArticlesByIds6())
                ->setArticleCountry('LT')
                ->setLang('LT')
                ->setOeNumbers(true)
                ->setThumbnails(true)
                ->setArticleId([$articleId]);


            $getDirectArticlesByIds6Response = $this->client->getDirectArticlesByIds6($getDirectArticlesByIds6)->getData();
            $thumbnails = count($getDirectArticlesByIds6Response) ? $getDirectArticlesByIds6Response[0]->getArticleThumbnails() : [];

            foreach ($getDirectArticlesByIds6Response[0]->getArticleThumbnails() as $thumb) {
                $img = new \stdClass();
                $img->name = $this->getPhotoLinkByDocId($thumb->getThumbDocId());
                $img->type = 'tecalliance';
                $images[] = $img;
            }
        }

        return $images;
    }

    public function getPhotosDocIdByProductSupplierReference($supplier_reference, $brand) {
        $images = [];
        $articleId = $this->getArticleIdByProductSupplierReference($supplier_reference, $brand);

        $getDirectArticlesByIds6 = (new getDirectArticlesByIds6())
            ->setArticleCountry('LT')
            ->setLang('LT')
            ->setOeNumbers(true)
            ->setThumbnails(true)
            ->setArticleId([$articleId]);


        $getDirectArticlesByIds6Response = $this->client->getDirectArticlesByIds6($getDirectArticlesByIds6)->getData();
        if ($getDirectArticlesByIds6Response) {
            foreach ($getDirectArticlesByIds6Response[0]->getArticleThumbnails() as $thumb) {
                $img = $thumb->getThumbDocId();
                $images[] = $img;
            }
        }

        return $images;
    }

    public function getImagesBySupplierReference($supplier_reference)
    {
        $getArticlesResponse = null;
        $imagesArr = [];

        if(isset($supplier_reference)) {
            $getArticles = (new GetArticles())
                ->setArticleCountry('LT')
                ->setLang('LT')
                ->setSearchQuery($supplier_reference)
                ->setIncludeAll(true);

            $getArticlesResponse = $this->client->getArticles($getArticles);
            $getArticlesResponse = $getArticlesResponse->getArticles();

            if(isset($getArticlesResponse[0])) {
                $images = $getArticlesResponse[0]->getImages();

                foreach ($images as $image) {
                    $image = (array)$image;
                    // Найти строку с самым большим числом в конце
                    $maxNumber = 0;
                    $imageValue = null;

                    foreach ($image as $property => $value) {
                        // Находим число в конце строки
                        preg_match('/(\d+)$/', $property, $matches);
                        $number = isset($matches[1]) ? (int)$matches[1] : 0;

                        // Сравниваем с текущим максимальным
                        if ($number > $maxNumber) {
                            $maxNumber = $number;
                            $imageValue = $value;
                        }
                    }

                    if($imageValue) {
                        $imagesArr[] = $imageValue;
                    }
                }
            }
        }

        return $imagesArr;
    }

    public function getPhotoLinkByDocId($docId) {
        return 'http://webservice.tecalliance.services/pegasus-3-0/documents/' . $this->providerId . '/' .
            $docId . '/0?api_key=' . $this->apiKey;
    }

    private function getEanFromArray(array $array): string
    {
        $ean = '';
        if ($array && is_array($array)) {
            foreach ($array as $key => $value) {
                $value = trim((string) $value);
                if(strlen($value) == 13) {
                    $ean = $value;
                }
            }
        }

        return $ean;
    }

    public function getArticleData(int $articleId, string $lang, bool $includeAll = false, bool $includeProductName = false, bool $includeParams = false)
    {
        $request = (new GetArticles())
            ->setArticleCountry('LT')//DE
            ->setLang($lang)
            ->setLegacyArticleIds([$articleId])
            ->setIncludeGenericArticles($includeProductName)
            ->setIncludeArticleCriteria($includeParams)
            ->setIncludeAll($includeAll);

        $response = $this->client->getArticles($request)->getArticles();
        $response = isset($response[0])
            ? $this->deepExtractObjectProperties($response[0])
            : [];

        if(isset($response["genericArticles"][0]["genericArticleDescription"])) {
            $response["productTypes"] = $response["genericArticles"][0]["genericArticleDescription"];
        }

        if(isset($response["articleCriteria"][0])) {
            $response["specifics"] = $this->getSpecifics($response["articleCriteria"]);
        }

        return $response;
    }

    public function getInfoByProductSupplierReference($reference, $brandId) {
        Log::add($this->logTraceId, 'get article id', 7);

        $articleId = $this->sendRequest([$this, 'getArticleIdByProductSupplierReference'], $reference, $brandId);

        $data = [];

        if($articleId) {
//            $ruData = $this->getArticleData($articleId, 'ru', false, true, true);
//            $enData = $this->getArticleData($articleId, 'en', false, true, true);
//            $deDataFull = $this->getArticleData($articleId, 'de', true);

            Log::add($this->logTraceId, 'get article data ru', 7);
            $ruDataFull = $this->sendRequest([$this, 'getArticleData'], $articleId, 'ru', true);

            Log::add($this->logTraceId, 'get article data en', 7);
            $enData = $this->sendRequest([$this, 'getArticleData'], $articleId, 'en', false, true, true);

            Log::add($this->logTraceId, 'get article data de', 7);
            $deData = $this->sendRequest([$this, 'getArticleData'], $articleId, 'de', false, true, true);

//            $data = $deDataFull;
            $data = $ruDataFull;
            $data["productTypes"] = [
                "ru" => $ruDataFull["productTypes"] ?? "",
                "en" => $enData["productTypes"] ?? "",
                "de" => $deData["productTypes"] ?? "",
            ];
            $data["specifics"] = [
                "ru" => $ruDataFull["specifics"] ?? "",
                "en" => $enData["specifics"] ?? "",
                "de" => $deData["specifics"] ?? "",
            ];

            $data['ean'] = $this->getEanFromArray($ruDataFull['gtins'] ?? []);

            Log::add($this->logTraceId, 'get compatibilities', 7);
            $data['compatibilities'] = $this->getArticleModels($articleId);
        } else {
            Log::add($this->logTraceId, 'Article Not Found', 7);
            $data['error'] = 'Article Not Found';
        }

        return $data;
    }

    protected function getSpecifics(array $articleCriteria): string
    {
        $result = [];

        foreach ($articleCriteria as $criteria) {
            if ((empty($criteria['formattedValue']) && empty($criteria['rawValue'])) || empty($criteria['criteriaDescription'])) {
                continue;
            }

            $description = $criteria['criteriaDescription'];
            $value = $criteria['formattedValue'] ?? $criteria['rawValue'];

            $result[] = sprintf('%s - %s', $description, $value);
        }

        return implode(",\n", $result);
    }

    public function getAllBrands()
    {
        $request = new GetAmBrands();
        $request = (new GetAmBrands())
            ->setArticleCountry('LT')//DE
            ->setLang('en');
        $brands = $this->client->GetAmBrands($request)->getData();

        return $brands;
    }

    private function setApiKeys()
    {
        $rootPath = dirname(__DIR__, 2);
        $tokensDir = $rootPath . '/tokens';
        $filePath = $tokensDir . '/tokens.json';

        if (file_exists($filePath)) {
            $json = file_get_contents($filePath);
            $data = json_decode($json, true);

            if(isset($data['providerId']) && $data['providerId'] && isset($data['key']) && $data['key']) {
                $this->apiKey = $data['key'];
                $this->providerId = (int) $data['providerId'];
                $this->client = new Client($this->apiKey, $this->providerId);
            } else {
                $this->scrapAndSetApiKeys();
            }
        } else {
            $this->scrapAndSetApiKeys();
        }

        return $data ?? [];
    }

    private function scrapAndSetApiKeys()
    {
        $url = "http://ebay_restapi_nginx/selenium/get_api_key";
        $response = Http::timeout(300)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
            ->get($url);

        $data = $response->json();

        if(isset($data['providerId']) && $data['providerId'] && isset($data['key']) && $data['key']) {
            $rootPath = dirname(__DIR__, 2);

            $tokensDir = $rootPath . '/tokens';

            $filePath = $tokensDir . '/tokens.json';

            if (!is_dir(__DIR__ . '/tokens')) {
                mkdir(__DIR__ . '/tokens', 0755, true);
            }

            file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));

            $this->providerId = (int) $data['providerId'];
            $this->apiKey = $data['key'];
            $this->client = new Client($this->apiKey, $this->providerId);
        }
    }

    private function sendRequest(callable $func, ...$args)
    {
        $result = $func(...$args);

        if(!$result) {
            $this->scrapAndSetApiKeys();
            $result = $func(...$args);
        }

        return $result;
    }
}