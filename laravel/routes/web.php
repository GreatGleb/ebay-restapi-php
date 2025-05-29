<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiEbayController;
use App\Jobs\UpdateProductsFromTecDoc;
use App\Jobs\UpdateProductsFromEbay;

Route::get('/updateEbay', [ApiEbayController::class, 'updateStockAndPrice'])->name('ebay.update');
Route::get('/exportEbay', [ApiEbayController::class, 'exportItems'])->name('ebay.export');

Route::get('', function () {
    return view('control_page');
});

Route::get('/update/products/fromTecDoc/db&sheets', function () {
    return view('syncDBandSheetsFroTecDoc');
})->name('syncDBandSheets.fromTecDoc');

Route::get('/jobs/update/products/fromTecDoc', function (Request $request) {
    $logTraceId = $request->header('log-trace-id');

    UpdateProductsFromTecDoc::dispatch($logTraceId);
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
