<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EbayController;
use App\Http\Controllers\API\ApiEbayController;

Route::get('/updateEbay', [ApiEbayController::class, 'updateStockAndPrice'])->name('ebay.update');
Route::get('/ebayRun', [EbayController::class, 'index'])->name('ebay.index');
Route::get('/exportEbay', [ApiEbayController::class, 'exportItems'])->name('ebay.export');
Route::get('/ebayControlPage', function () {
    return view('ebay.ebay');
})->name('ebay.controlPage');