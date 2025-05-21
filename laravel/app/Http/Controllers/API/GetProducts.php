<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Product;

class GetProducts extends Controller
{
    public function run() {
        $products = Product::where('published_to_ebay_de', false)
            ->orderBy('id')
            ->get();

        $brandNames = $products->pluck('producer_brand')->filter()->unique()->map(fn($name) => strtolower($name))->toArray();
        $brands = DB::table('producer_brands')
            ->select('name', 'tecdoc_id')
            ->whereIn(DB::raw('LOWER(name)'), $brandNames)
            ->get()
            ->keyBy(fn($brand) => strtolower($brand->name));

        $compatibilities = DB::table('product_compatibilities')
            ->whereIn('product_id', $products->pluck('id'))
            ->select('product_id', 'car_tecdoc_id')
            ->get()
            ->groupBy('product_id');

        $oeCodes = DB::table('product_oe_codes')
            ->whereIn('product_id', $products->pluck('id'))
            ->select('product_id', 'number')
            ->get()
            ->groupBy('product_id');

        $photos = DB::table('product_photos')
            ->whereIn('product_id', $products->pluck('id'))
            ->select('product_id', 'original_photo_url')
            ->get()
            ->groupBy('product_id');

        $products->transform(function ($product) use ($compatibilities, $brands, $oeCodes, $photos) {
            $brandKey = strtolower($product->producer_brand);
            $product->producer_tecdoc_id = $brands[$brandKey]->tecdoc_id ?? null;

            if(isset($compatibilities[$product->id])) {
                $product->car_tecdoc_ids = $compatibilities[$product->id]->pluck('car_tecdoc_id')->toArray() ?? [];
            } else {
                $product->car_tecdoc_ids = [];
            }

            if(isset($oeCodes[$product->id])) {
                $product->oe_codes = $oeCodes[$product->id]->pluck('number')->toArray() ?? [];
            } else {
                $product->oe_codes = [];
            }

            if(isset($photos[$product->id])) {
                $product->photos = $photos[$product->id]->pluck('original_photo_url')->toArray() ?? [];
            } else {
                $product->photos = [];
            }

            return $product;
        });

        $products = $products->toArray();

        return $products;
    }

}
