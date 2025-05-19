<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Product;
use App\Models\ProducerBrand;
use Illuminate\Support\Facades\Http;
use JetBrains\PhpStorm\NoReturn;


class UpdateProducts extends Controller
{
    public function run(Request $request) {
        $products = $request->all();

        $allowedPropertiesInTableProducts = Schema::getColumnListing('products');
        $productsFiltered = collect($products)->map(function ($item) use ($allowedPropertiesInTableProducts) {
            return array_intersect_key($item, array_flip($allowedPropertiesInTableProducts));
        })->toArray();

        $updateFields = array_keys($productsFiltered[0]);

        $resultOfUpdating = Product::upsert($productsFiltered, ['id'], $updateFields);

//            $photos = $product['photos'];
//            $oe_codes = $product['oe_codes'];
//            $car_compatibilities = $product['cars_compatibilities'];

        return [$resultOfUpdating, $updateFields];
    }

    #[NoReturn] public function fromTecDoc(): array
    {
        $products = Product::query()
            ->select('products.*', 'producer_brands.tecdoc_id as producer_tecdoc_id')
            ->leftJoin('producer_brands', function ($join) {
                $join->on(
                    DB::raw('LOWER(products.producer_brand)'),
                    '=',
                    DB::raw('LOWER(producer_brands.name)')
                );
            })
            ->where('products.published_to_ebay_de', false)
            ->orderBy('products.id')
            ->get();

        $requestForTecDoc = [];
        foreach ($products as $product) {
            $brandId = $product->producer_tecdoc_id;
            $reference = $product->tecdoc_number;

            $requestForTecDoc[] = [
                'id' => $product->id,
                'reference' => $reference,
                'brand_id' => $brandId,
            ];
        }

        $url = "http://ebay_restapi_nginx/tecdoc/products-info";
        $response = Http::timeout(300)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($url, $requestForTecDoc);

        // Получить ответ
        $data = $response->json();

        dd($data);

        $productUpdateData = [];
        $productUpdateFields = [
            'specifics_ru',
        ];

        foreach ($data as $tecDocProduct) {
            $specifics = $this->getSpecifics($tecDocProduct['articleCriteria']);

            $productUpdateData[] = [
                'id' => $tecDocProduct['id'],
                'specifics_ru' => $specifics,
            ];
        }

        $resultOfUpdatingProducts = Product::upsert($productUpdateData, ['id'], $productUpdateFields);

//        echo $data;
        dd($resultOfUpdatingProducts, $productUpdateData);

//            $photos = $product['photos'];
//            $oe_codes = $product['oe_codes'];
//            $car_compatibilities = $product['cars_compatibilities'];

        return [];
    }

    public function brands(): void
    {
        ProducerBrand::insert([
            "name" => "MAXGEAR",
            "tecdoc_id" => 403
        ]);
    }

    /**
     * Формирует строку характеристик из массива критериев
     *
     * @param array $articleCriteria Массив критериев товара
     * @return string Отформатированная строка характеристик
     */
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
}
