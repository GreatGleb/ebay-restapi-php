<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPhoto;

class ProductController
{
    public function getEbayProductHtml($productId) {
        $product = Product::where('id', $productId)->first()->toArray();
        $photos = ProductPhoto::where('product_id', $productId)->first()->toArray();

        if(isset($product['specifics_de'])) {
            $specifics = [];

            $lines = explode("\n", $product['specifics_de']);
            foreach ($lines as $line) {
                $line = trim($line);
                $line = rtrim($line, ", \t\r\0\x0B");
                if ($line === '') continue;

                $explodedLine = array_map('trim', explode(' - ', $line, 2));

                if (count($explodedLine) === 2) {
                    [$name, $value] = $explodedLine;
                    $specifics[] = ['name' => $name, 'value' => $value];
                } else {
                    dd('Problem specific line:', $product['specifics_de'], $line);
                }
            }

            $product['specifics_de'] = $specifics;
        }

        if($photos) {
            $product['photo'] = $photos['cortexparts_photo_url'] ?? null;
        }

        $thisYear = date('Y');

        $html = view('products.ebayItem.de', ['product' => $product, 'thisYear' => $thisYear])->render();

        return $html;
    }
}
