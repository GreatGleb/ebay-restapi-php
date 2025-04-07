@extends('layouts.app')

@section('title','Ebay - Example of using Ebay Rest Api')
@section('content')
    <link href="{{ asset('css/ebay.css') }}" rel="stylesheet">

    <h1>Ebay - Example of using Ebay Rest Api</h1>

    <form data-action="{{ route('import.update') }}" method="post" enctype="multipart/form-data"  onsubmit="return false">
        <div>
            <h3>Importuoti Update</h3>
            <div class="row">
                <div>
                    <input type="file" id="import" name="import">
                </div>
                <div class="update_details">
                    <input type="checkbox" checked="1" name="all">
                    <label for="all">Update all</label>
                    <input type="checkbox" checked="1" name="title">
                    <label for="title">title</label>
                    <input type="checkbox" checked="1" name="newTitle">
                    <label for="newTitle">new title</label>
                    <input type="checkbox" checked="1" name="description">
                    <label for="description">description</label>
                    <input type="checkbox" checked="1" name="pictures">
                    <label for="pictures">images</label>
                    <input type="checkbox" checked="1" name="categoryId">
                    <label for="categoryId">category</label>
                    <input type="checkbox" checked="1" name="quantity">
                    <label for="quantity">quantity</label>
                    <input type="checkbox" checked="1" name="price">
                    <label for="price">price</label>
                    <input type="checkbox" checked="1" name="compatibility">
                    <label for="compatibility">compatibilities</label>
                    <input type="checkbox" checked="1" name="deliveryMethod">
                    <label for="deliveryMethod">delivery method</label></br>
                    <input type="checkbox" checked="1" name="specifications">
                    <label for="specifications">specifications:</label>
                    <input type="checkbox" checked="1" name="hersteller">
                    <label for="hersteller">hersteller</label>,
                    <input type="checkbox" checked="1" name="produkttyp">
                    <label for="produkttyp">producttyp</label>,
                    <input type="checkbox" checked="1" name="garantie">
                    <label for="garantie">herstellergarantie</label>,
                    <input type="checkbox" checked="1" name="oe">
                    <label for="oe">oe referenznummer(n)</label>,
                    <input type="checkbox" checked="1" name="nummer">
                    <label for="nummer">herstellernummer</label>,
                    <input type="checkbox" checked="1" name="ean">
                    <label for="ean">EAN</label>,
                    <input type="checkbox" checked="1" name="country">
                    <label for="country">country</label>,
                    <input type="checkbox" checked="1" name="length">
                    <label for="length">length</label>,
                    <input type="checkbox" checked="1" name="position">
                    <label for="position">position</label>,
                    <input type="checkbox" checked="1" name="keywords">
                    <label for="keywords">keywords</label></br>
                </div>
                <button class="btn btn-sm btn-primary" type="submit">Importuoti</button>
            </div>
        </div>
    </form>

    <form data-action="{{ route('import.add') }}" method="post" enctype="multipart/form-data"  onsubmit="return false">
        <div>
            <h3>Importuoti Add</h3>
            <div class="row">
                <div>
                    <input type="file" id="import" name="import">
                </div>
                <button class="btn btn-sm btn-primary" type="submit">Importuoti</button>
            </div>
        </div>
    </form>

    @include('ebay.ebayLoading')

    <script src="{{asset('js/ebayUpdateImport.js')}}"></script>
    <script src="{{asset('js/ebayLoading.js')}}"></script>
@endsection
