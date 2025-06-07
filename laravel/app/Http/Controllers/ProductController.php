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

            $lines = explode(',', $product['specifics_de']);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;

                [$name, $value] = array_map('trim', explode(' - ', $line, 2));
                $specifics[] = ['name' => $name, 'value' => $value];
            }

            $product['specifics_de'] = $specifics;
        }

        if($photos) {
            $product['photo'] = $photos['cortexparts_photo_url'] ?? null;
        }

        $html = view('products.ebayItem.de', ['product' => $product])->render();

        return $html;
    }
}
