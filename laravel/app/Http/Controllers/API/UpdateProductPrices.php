<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;

class UpdateProductPrices extends Controller
{
    public float $vatRate = 1.23;
    public function run(Request $request = null, $profitPercentage = null) {
        $profitPercentage = $request->profitPercentage ?? $profitPercentage ?? 30;
        $profitMultiplier = (float) $profitPercentage;
        $profitMultiplier = $profitMultiplier/100;
        $profitMultiplier = 1 + $profitMultiplier;

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
                'supplier_price_gross' => round($basePrice * $this->vatRate, 2),
                'retail_price_net' => round($basePrice * $profitMultiplier, 2),
                'retail_price_gross' => round(($basePrice * $profitMultiplier) * $this->vatRate, 2),
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
