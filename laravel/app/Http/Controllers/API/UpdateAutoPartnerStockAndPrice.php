<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;

class UpdateAutoPartnerStockAndPrice extends Controller
{
    public function run() {
        // to do: use chunks

        // get all products from autopartner
        $products = Product::query()
            ->where('supplier', 'AutoPartner')
            ->whereNot('reference', null)
            ->orderBy('products.id')
            ->get()
            ->toArray();

        // create array [reference] with stocks 0, price like price for all products
        $productUpdateData = [];

        foreach ($products as $product) {
            $data = [
                'id' => $product['id'],
                'stock_quantity_pl' => 0,
                'stock_quantity_pruszkow' => 0,
                'supplier_price_net' => $product['supplier_price_net'],
            ];
            $productUpdateData[$product['reference']] = $data;
        }

        // for autopartner_stock and autopartner_price, save all to array [reference]

        // to do: path from storage
        $stockFilename = realpath(__DIR__.'/../../../../storage/app/public/suppliers') . '/autopartner_stock.csv';
        $priceFilename = realpath(__DIR__.'/../../../../storage/app/public/suppliers') . '/autopartner_price.csv';

        $stockFile = fopen($stockFilename, "rb");
        $priceFile = fopen($priceFilename, "rb");
        if ($stockFile) {
            while (($line = fgets($stockFile)) !== false) {
                $line = explode(';', $line);
                $code = $line[0];

                if (!isset($productUpdateData[$code])) {
                    continue;
                }

                $stockQuantity = (int)$line[1];
                if($line[2] == '01') {
                    $productUpdateData[$code]['stock_quantity_pl'] = $stockQuantity;
                } else {
                    $productUpdateData[$code]['stock_quantity_pruszkow'] = $stockQuantity;
                }
            }
        } else {
            var_dump('error stock file');
        }

        if ($priceFile) {
            while (($line = fgets($priceFile)) !== false) {
                $line = explode(';', $line);
                $code = $line[0];

                if (!isset($productUpdateData[$code])) {
                    continue;
                }

                $price = (float) $line[5];
                $productUpdateData[$code]['supplier_price_net'] = $price;
            }
        } else {
            var_dump('error price file');
        }

        // update db
        $productUpdateFields = [
            'stock_quantity_pl',
            'stock_quantity_pruszkow',
            'supplier_price_net',
        ];

        $resultOfUpdatingProducts = Product::upsert($productUpdateData, ['id'], $productUpdateFields);

        return [
            'resultOfUpdatingProducts' => $resultOfUpdatingProducts
        ];
    }
}
