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

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    function getProductInfoByOeCodes(Request $request)
    {
        $logTraceId = getallheaders()['Log-Trace-Id'];

        try {
            $oeCodesString = $request->query('reference');

            if (!$oeCodesString) {
                return "Артикул не передан";
            }

            $rawArray = preg_split('/[\/,]+/', $oeCodesString);

            // 2. Очищаем каждый элемент:
            // trim() уберет пробелы по краям, но оставит их внутри (как в "535 0174 10")
            $oeCodesRaw = array_values(array_filter(array_map('trim', $rawArray)));
            $oeCodes = [];

            foreach ($oeCodesRaw as $key => $code) {
                $parts = explode(' ', $code);
                $totalParts = count($parts);

                // Если частей больше одной, анализируем их длину
                if ($totalParts > 1) {
                    $isSeparateCodes = false;
                    $longPartsCount = 0;

                    foreach ($parts as $part) {
                        // Если часть длинная (например, >= 6 символов), считаем ее "крупной"
                        if (strlen($part) >= 6) {
                            $longPartsCount++;
                        }
                    }

                    // Если большинство частей "крупные", значит это разные коды,
                    // которые случайно оказались через пробел.
                    // Ищем только по последней части (как по наиболее вероятному артикулу)
                    if ($longPartsCount >= 1) {
                        foreach ($parts as $part) {
                            $oeCodes[] = $part;
                        }
                    } else {
                        // Иначе (когда части мелкие) считаем это одним целым кодом
                        $oeCodes[] = $code;
                    }
                } else {
                    $oeCodes[] = $code;
                }
            }

            $tecdoc = new TecDocController(null);
            $stats = [];
            $itemStorage = []; // Храним здесь объекты, чтобы не перезаписывать их

            foreach ($oeCodes as $code) {
                $searchData = $tecdoc->searchRequest($code);
                if (!$searchData) continue;

                // Используем множество для этого кода, чтобы не учитывать дубли внутри одного поиска
                $foundInThisCode = [];

                foreach ($searchData as $item) {
                    $id = $item['articleId'];

                    // Сохраняем объект, если видим его впервые
                    if (!isset($itemStorage[$id])) {
                        $itemStorage[$id] = $item;
                    }

                    $foundInThisCode[$id] = true;
                }

                // Прибавляем балл только 1 раз за этот конкретный OE-код
                foreach (array_keys($foundInThisCode) as $id) {
                    if (!isset($stats[$id])) {
                        $stats[$id] = 0;
                    }
                    $stats[$id]++;
                }
            }

    // Собираем итоговый массив
            $finalList = [];
            foreach ($stats as $id => $hits) {
                $finalList[] = [
                    'item' => $itemStorage[$id],
                    'hits' => $hits
                ];
            }

            // Сортируем по популярности (hits)
            usort($finalList, function($a, $b) {
                return $b['hits'] <=> $a['hits'];
            });

    // Берем топ-10
            $result = array_slice($finalList, 0, 10);

            $data = [];

            if(isset($result[0]['item']['articleId'])) {
                $articleId = $result[0]['item']['articleId'];
                $data = $tecdoc->getInfoByArticleId($articleId);
            }

            header('Content-Type: application/json');
            return json_encode(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::add($logTraceId, "TecDoc Error: " . $e->getMessage(), 3);

            header('Content-Type: application/json');
            return json_encode(['success' => false, 'message' => 'Сервис поиска временно недоступен']);
        }
    }

    function testGetProductInfo($reference) {
        $tecdoc = new TecDocController(null);

//        $brands = $tecdoc->getAllBrands();
////        $articleData = $tecdoc->getArticleData($reference, 'ru', true);
//
//        foreach ($brands as $brand) {
//            echo $brand['brandName'];
//            echo ', ';
//        }

        echo "
<script>
    window.addEventListener('load', function() {
        // Находим все элементы с классом sf-dump-compact (это свернутые узлы)
        // и удаляем этот класс, чтобы они стали видимыми
        document.querySelectorAll('.sf-dump-compact').forEach(function(el) {
            el.classList.remove('sf-dump-compact');
        });
        // Дополнительно разворачиваем вложенные стрелочки
        document.querySelectorAll('.sf-dump-toggle').forEach(function(el) {
            if (el.innerText === '▶') {
                el.click();
            }
        });
    });
</script>
";

        $searched = $tecdoc->search($reference);
        dump($searched);
        dump('$searched');

//        $articleId = $tecdoc->getArticleIdByProductSupplierReference($reference, null);
//        dump($articleId);
//        dump('$articleId');
//        $byBrand = $tecdoc->getInfoByProductSupplierReference($reference, 403);
//        dump($byBrand);
//        dump('$byBrand');
//        dump($articleData);
//        dump('$articleData');

        return [];
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