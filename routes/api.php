<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiEbayController;

Route::get('/ebay', [ApiEbayController::class, 'hashCallback']);
Route::get('/ebay/run', [ApiEbayController::class, 'index']);
Route::post('/ebay/revise', [ApiEbayController::class, 'importUpdate'])->name('import.update');
Route::post('/ebay/add', [ApiEbayController::class, 'importAdd'])->name('import.add');
Route::post('/ebay/checkImportLoading', [ApiEbayController::class, 'checkImportLoading'])->name('ebay.checkImport');
Route::post('/ebay/updateEbay', [ApiEbayController::class, 'updateStockAndPrice'])->name('ebay.update');
Route::post('/ebay/exportEbay', [ApiEbayController::class, 'exportItems'])->name('ebay.export');

Route::get('/ebay/getLinkFirstAuth', [ApiEbayController::class, 'getLinkFirstAuth'])->name('ebay.linkFirstAuth');
Route::get('/ebay/getCategories', [ApiEbayController::class, 'getCategories'])->name('ebay.getCategories');
Route::post('/ebay/getItemAspectsForCategory', [ApiEbayController::class, 'getItemAspectsForCategory'])->name('ebay.getItemAspectsForCategory');
Route::get('/ebay/getRateLimits', [ApiEbayController::class, 'getRateLimits'])->name('ebay.getRateLimits');
Route::get('/ebay/getFulfillmentPolicies', [ApiEbayController::class, 'getFulfillmentPolicies'])->name('ebay.getFulfillmentPolicies');
Route::get('/ebay/getPaymentPolicies', [ApiEbayController::class, 'getPaymentPolicies'])->name('ebay.getPaymentPolicies');
Route::get('/ebay/getReturnPolicies', [ApiEbayController::class, 'getReturnPolicies'])->name('ebay.getReturnPolicies');
Route::post('/ebay/getItem', [ApiEbayController::class, 'getItem'])->name('ebay.getItem');
Route::get('/ebay/getSellerList', [ApiEbayController::class, 'getSellerList'])->name('ebay.getSellerList');
Route::post('/ebay/reviseItem', [ApiEbayController::class, 'reviseItem'])->name('ebay.reviseItem');
Route::post('/ebay/reviseInventory', [ApiEbayController::class, 'reviseInventory'])->name('ebay.reviseInventory');
Route::post('/ebay/addItem', [ApiEbayController::class, 'addItem'])->name('ebay.addItem');
Route::post('/ebay/updatingItem', [ApiEbayController::class, 'updatingItem'])->name('ebay.updatingItem');
Route::post('/ebay/updatingInventory', [ApiEbayController::class, 'updatingInventory'])->name('ebay.updatingInventory');
Route::post('/ebay/addingItem', [ApiEbayController::class, 'addingItem'])->name('ebay.addingItem');
Route::post('/ebay/checkIfItemExists', [ApiEbayController::class, 'checkIfItemExists'])->name('ebay.checkIfItemExists');
Route::post('/ebay/updatePostalCodes', [ApiEbayController::class, 'updatePostalCodes'])->name('ebay.updatePostalCodes');