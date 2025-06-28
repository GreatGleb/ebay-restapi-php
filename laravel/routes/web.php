<?php

use App\Console\Commands\PlanUploadingProductsToEbay;
use App\Jobs\UploadScheduledProductsToEbay;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiEbayController;
use App\Http\Controllers\ProductController;
use App\Jobs\CollectProductData;
use App\Jobs\UpdateProductsFromEbay;

Route::get('/exportEbay', [ApiEbayController::class, 'exportItems'])->name('ebay.export');

Route::get('', function () {
    return view('control_page');
});

Route::get('/test', function () {
    return view('test'); // будет искать resources/views/test.blade.php
});

Route::get('products/ebay/getHTML/{id}', [ProductController::class, 'getEbayProductHtml']);

Route::get('/update/products/collectData', function () {
    return view('collectDataForNewProductsFromSheets');
})->name('syncDBandSheets.collectData');

Route::get('/jobs/update/products/collectData', function (Request $request) {
    $logTraceId = $request->header('log-trace-id');

    CollectProductData::dispatch($logTraceId);

    return response()->json(['status' => 'Job dispatched']);
});

Route::get('/jobs/update/products/fromEbay', function (Request $request) {
    $logTraceId = $request->header('log-trace-id');

    UpdateProductsFromEbay::dispatch($logTraceId);
    return response()->json(['status' => 'Job dispatched']);
});

Route::get('/update/products/fromEbay/db&sheets', function () {
    return view('syncDBandSheetsFromEbay');
})->name('syncDBandSheets.fromEbay');

Route::get('/jobs/update/products/PlanUploadingProductsToEbay', function () {
    $job = new PlanUploadingProductsToEbay();
    $job->handle();

    return response()->json(['status' => 'Job dispatched']);
});
