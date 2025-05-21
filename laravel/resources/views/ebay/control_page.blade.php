@extends('layouts.app')

@section('title','Control eBay')
@section('content')
    <link href="{{ asset('css/ebay.css') }}" rel="stylesheet">

    <h1>Control eBay</h1>

    <h2>Products</h2>
    <p>
        <a href="http://localhost/python/products/save_to_db_from_google_sheets" target="_blank">Update DB products from Google sheets</a>
    </p>
    <p>
        <a href="{{ route('updateProducts.fromTecDoc') }}" target="_blank">Update DB products from TecDoc</a>
    </p>
    <p>
        <a href="http://localhost/python/products/update_categories_in_google_sheets" target="_blank">Update product categories Google sheets</a>
    </p>

    <h2>Categories</h2>
    <p>
        <a href="{{ route('ebay.getCategoriesText') }}" target="_blank">Get categories text from Ebay</a>
    </p>
    <p>
        <a href="http://localhost/python/categories/save_to_db_from_google_sheets" target="_blank">Update DB categories from Google sheets</a>
    </p>

    <h2>Producer brands</h2>
    <p>
        <a href="{{ route('updateBrands') }}" target="_blank">Update DB producer brands</a>
    </p>
@endsection
