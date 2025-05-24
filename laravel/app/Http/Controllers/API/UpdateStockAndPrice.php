<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\Product;

class UpdateStockAndPrice extends Controller
{
    public float $vatRate = 1.21;
    public function run($profitPercent = 30) {
        $profitExecuteNumber = (float) $profitPercent;
        $profitExecuteNumber = $profitExecuteNumber/100;
        $profitExecuteNumber = 1 + $profitExecuteNumber;

        $products = Product::query()
            ->whereNot('supplier_price_net', null)
            ->orderBy('products.id')
            ->get()
            ->toArray();

        $productUpdateData = [];

        foreach ($products as $product) {
            $basePrice = (float) $product['supplier_price_net'];
            $data = [
                'id' => $product['id'],
                'supplier_price_gross' => $basePrice * $this->vatRate,
                'retail_price_net' => $basePrice * $profitExecuteNumber,
                'retail_price_gross' => ($basePrice * $profitExecuteNumber) * $this->vatRate,
            ];
            $productUpdateData[] = $data;
        }

        $productUpdateFields = [
            'supplier_price_gross',
            'retail_price_net',
            'retail_price_gross',
        ];

        $resultOfUpdatingProducts = Product::upsert($productUpdateData, ['id'], $productUpdateFields);

        return [
            'resultOfUpdatingProducts' => $resultOfUpdatingProducts
        ];
    }

}
