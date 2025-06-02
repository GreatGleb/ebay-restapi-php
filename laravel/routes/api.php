<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\ApiEbayController;
use App\Http\Controllers\API\UpdateProducts;
use App\Http\Controllers\API\UpdateProductPhotos;
use App\Http\Controllers\API\UpdateAutoPartnerStockAndPrice;
use App\Http\Controllers\API\UpdateStockAndPrice;
use App\Http\Controllers\API\GetProducts;
use App\Http\Controllers\API\GetJsonFiles;

Route::post('/update/products', [UpdateProducts::class, 'run']);
Route::get('/update/products/fromTecDoc', [UpdateProducts::class, 'fromTecDoc'])->name('updateProducts.fromTecDoc');
Route::get('/update/products/fromEbay', [UpdateProducts::class, 'fromEbay'])->name('updateProducts.fromEbay');
Route::get('/update/products/photos', [UpdateProductPhotos::class, 'run'])->name('updateProducts.photos');
Route::get('/update/brands', [UpdateProducts::class, 'brands'])->name('updateBrands');
Route::get('/ebay/getCategoriesText', [ApiEbayController::class, 'getCategoriesText'])->name('ebay.getCategoriesText');

Route::get('/get/products', [GetProducts::class, 'run']);

Route::get('/getTableSchema', [GetJsonFiles::class, 'getTableSchema']);

Route::get('/update/supplierStockAndPrice/autopartner', [UpdateAutoPartnerStockAndPrice::class, 'run'])->name('updateProductStockAndPrice.supplier.autoPartner');
Route::get('/update/stockAndPrice/calculate', [UpdateStockAndPrice::class, 'run'])->name('updateProductStockAndPrice.calculate');

Route::get('/ebay/run', [ApiEbayController::class, 'index']);
Route::post('/ebay/revise', [ApiEbayController::class, 'importUpdate'])->name('import.update');
Route::post('/ebay/add', [ApiEbayController::class, 'importAdd'])->name('import.add');
Route::post('/ebay/checkImportLoading', [ApiEbayController::class, 'checkImportLoading'])->name('ebay.checkImport');
Route::post('/ebay/updateEbay', [ApiEbayController::class, 'updateStockAndPrice'])->name('ebay.update');
Route::post('/ebay/exportEbay', [ApiEbayController::class, 'exportItems'])->name('ebay.export');

Route::get('/ebay/getLinkFirstAuth', [ApiEbayController::class, 'getLinkFirstAuth'])->name('ebay.linkFirstAuth');
Route::get('/ebay/setRefreshToken', [ApiEbayController::class, 'setRefreshToken'])->name('ebay.setRefreshToken');
Route::get('/ebay/getCategories', [ApiEbayController::class, 'getCategories'])->name('ebay.getCategories');
Route::post('/ebay/getItemAspectsForCategory', [ApiEbayController::class, 'getItemAspectsForCategory'])->name('ebay.getItemAspectsForCategory');
Route::get('/ebay/getCategoryByName/{name}', [ApiEbayController::class, 'getCategoryByName'])->name('ebay.getCategoryByName');
Route::get('/ebay/getItemsByEAN/{name}', [ApiEbayController::class, 'getItemsByEAN'])->name('ebay.getItemsByEAN');
Route::post('/ebay/searchItemsByProducts', [ApiEbayController::class, 'searchItemsByProducts'])->name('ebay.searchItemsByProducts');
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
