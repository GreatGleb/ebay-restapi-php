<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiEbayController;
use App\Jobs\UpdateProductsFromTecDoc;

Route::get('/updateEbay', [ApiEbayController::class, 'updateStockAndPrice'])->name('ebay.update');
Route::get('/exportEbay', [ApiEbayController::class, 'exportItems'])->name('ebay.export');

Route::get('', function () {
    return view('control_page');
});

Route::get('/update/products/fromTecDoc/db&sheets', function () {
    return view('updateDbFromTecDoc');
})->name('updateFromTecDoc&SyncDB&Sheets');

Route::get('/jobs/update/products/fromTecDoc', function (Request $request) {
    $logTraceId = $request->header('log-trace-id');

    UpdateProductsFromTecDoc::dispatch($logTraceId);
    return response()->json(['status' => 'Job dispatched']);
});
