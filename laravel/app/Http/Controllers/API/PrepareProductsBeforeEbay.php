<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\ProductController;
use App\Models\Product;

class PrepareProductsBeforeEbay
{
    public function html($logTraceId = null) {
        $queryProducts = Product::
            where('products.published_to_ebay_de', false)
            ->whereNotNull('products.ean')
            ->orderBy('products.id');

        $queryProducts->chunk(10, function ($products) use ($logTraceId) {
            $requestForUpdateHTMLColumnInDB = [];

            foreach ($products as $product) {
                $productController = new ProductController();
                $generatedHTML = $productController->getEbayProductHtml($product['id']);

                $requestForUpdateHTMLColumnInDB[] = [
                    'id' => $product['id'],
                    'description_to_ebay_de' => $generatedHTML
                ];
            }

            Product::upsert($requestForUpdateHTMLColumnInDB, ['id'], ['description_to_ebay_de']);
        });
    }
}
