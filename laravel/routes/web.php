<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiEbayController;

Route::get('/updateEbay', [ApiEbayController::class, 'updateStockAndPrice'])->name('ebay.update');
Route::get('/exportEbay', [ApiEbayController::class, 'exportItems'])->name('ebay.export');

Route::get('', function () {
    return view('control_page');
});

Route::get('/update/products/fromTecDoc', function () {
    return view('updateDbFromTecDoc');
})->name('updateProducts.fromTecDoc');

Route::get('main/', function () {
    return view('ebay.ebay');
});
