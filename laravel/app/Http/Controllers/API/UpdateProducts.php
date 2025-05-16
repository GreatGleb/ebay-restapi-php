<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Schema;

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

        return [$resultOfUpdating];
    }
}
