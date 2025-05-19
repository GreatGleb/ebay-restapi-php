<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiEbayController;

Route::get('/updateEbay', [ApiEbayController::class, 'updateStockAndPrice'])->name('ebay.update');
Route::get('/exportEbay', [ApiEbayController::class, 'exportItems'])->name('ebay.export');

Route::get('', function () {
    return view('ebay.control_page');
})->name('ebay.controlPage');

Route::get('main/', function () {
    return view('ebay.ebay');
});
