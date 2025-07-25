@extends('layouts.app')

@section('title','Control eBay')
@section('content')
    <link href="{{ asset('css/ebay.css') }}" rel="stylesheet">

    <h1>Control Products Data</h1>

    <h2>Products - sync DB & Google Sheets</h2>
    <p>
        <a href="{{ route('syncDBandSheets.collectData') }}" target="_blank">Collect data for new products from Google Sheets</a>
    </p>
    <p>
        <a href="{{ route('syncDBandSheets.fromEbay') }}" target="_blank">Sync DB & Google Sheets products from eBay</a>
    </p>

    <h2>Products - DB</h2>
    <p>
        <a href="http://localhost/python/products/save_to_db_from_google_sheets" target="_blank">Add/Update DB products from Google Sheets</a>
    </p>
    <p>
        <a href="{{ route('updateProducts.fromTecDoc') }}" target="_blank">Update DB new products from TecDoc</a>
    </p>
    <p>
        <a href="{{ route('ebay.prepareXMLtoAddItems') }}" target="_blank">To DB prepare&save xml before EXPORT new items to eBay</a>
    </p>
    <p>
        <a href="{{ route('ebay.prepareXMLtoUpdateToEbay') }}" target="_blank">To DB prepare&save xml before UPDATE items to eBay</a>
    </p>
    <p>
        <a href="{{ route('updateProducts.setOrderUploadingToEbay') }}" target="_blank">To DB set order of uploading new products to eBay</a>
    </p>
    <h3>Update stock and price</h3>
    <p>
        <a href="{{ route('updateProductStockAndPrice.supplier.autoPartner') }}" target="_blank">Update DB products - stock and price from AutoPartner</a>
    </p>
    <p>
        <a href="{{ route('updateProductStockAndPrice.calculate', ['profitPercentage' => 30]) }}" target="_blank">Update DB products - stock and price - calculate VAT taxes and retail price</a>
    </p>

    <h2>Products - Google Sheets</h2>
    <p>
        <a href="http://localhost/python/products/update_from_db_to_google_sheets" target="_blank">Update Google Sheets products from DB</a>
    </p>
    <p>
        <a href="http://localhost/python/products/update_categories_in_google_sheets" target="_blank">Update Google Sheets product categories</a>
    </p>

    <h2>Categories</h2>
    <p>
        <a href="{{ route('ebay.getCategoriesText') }}" target="_blank">Get categories text from Ebay</a>
    </p>
    <p>
        <a href="http://localhost/python/categories/save_to_db_from_google_sheets" target="_blank">Update DB categories from Google Sheets</a>
    </p>

    <h2>Producer brands</h2>
    <p>
        <a href="{{ route('updateBrands') }}" target="_blank">Update DB producer brands</a>
    </p>
@endsection
