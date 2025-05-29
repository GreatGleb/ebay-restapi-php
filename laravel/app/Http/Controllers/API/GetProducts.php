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
            ->get()
            ->groupBy('product_id');

        $ebaySimilarProducts = DB::table('product_ebay_similar_products')
            ->whereIn('product_id', $products->pluck('id'))
            ->get()
            ->groupBy('product_id');

        $products->transform(function ($product) use ($compatibilities, $brands, $oeCodes, $photos, $ebaySimilarProducts) {
            $brandKey = strtolower($product->producer_brand);
            $product->producer_tecdoc_id = $brands[$brandKey]->tecdoc_id ?? null;

            if(isset($compatibilities[$product->id])) {
                $product->car_compatibilities = $compatibilities[$product->id]->pluck('car_tecdoc_id')->toArray() ?? [];
            } else {
                $product->car_compatibilities = [];
            }

            if(isset($oeCodes[$product->id])) {
                $product->oe_codes = $oeCodes[$product->id]->pluck('number')->toArray() ?? [];
            } else {
                $product->oe_codes = [];
            }

            if(isset($ebaySimilarProducts[$product->id][0])) {
                $ebaySimilarProductsName = $ebaySimilarProducts[$product->id][0]->names;
                $ebaySimilarProductsName = json_decode($ebaySimilarProductsName, true);
                $ebaySimilarProductsName = implode("\n", $ebaySimilarProductsName);
                $product->ebay_similar_products_name = $ebaySimilarProductsName;
                $product->ebay_similar_products_photo = $ebaySimilarProducts[$product->id][0]->photo;
            } else {
                $product->oe_codes = [];
            }

            if(isset($photos[$product->id])) {
                $productPhotos = [];
                $productPhotos['links'] = $photos[$product->id]->pluck('original_photo_url')->toArray() ?? [];

                $withLogo = $photos[$product->id][0]->cortexparts_photo_url ?? false;
                if ($withLogo) {
                    $withLogo = true;
                }
                $productPhotos['withLogo'] = $withLogo;

                if(isset($productPhotos['links'][0])) {
                    $product->photo = $productPhotos['links'][0];
                }

                $product->photos = $productPhotos;
            } else {
                $product->photos = [];
            }

            return $product;
        });

        $products = $products->toArray();

        return $products;
    }

}
