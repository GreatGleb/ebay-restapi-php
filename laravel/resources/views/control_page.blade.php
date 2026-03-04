@extends('layouts.app')

@section('title','Control eBay')
@section('content')
    <link href="{{ asset('css/ebay.css') }}" rel="stylesheet">

    <h1>Control Products Data</h1>

    {{-- Первый пункт: Синхронизация --}}
    <div class="control-section">
        <a href="{{ route('syncDBandSheets.collectData') }}" target="_blank" class="main-link">
            <h2>Sync & Update Products</h2>
        </a>
        <p class="description">Downloads new items from Google Sheets and fills data from all sources (Gemini, TecDoc, eBay).</p>
    </div>

    <hr>

    {{-- Второй пункт: Синхронизация --}}
    <div class="control-section">
        <a href="{{ route('syncDBandSheets.fromEbay') }}" target="_blank" class="main-link">
            <h2>Just Sync</h2>
        </a>
        <p class="description">Downloads new items from Google Sheets and fills data to DB.</p>
    </div>

    <hr>

    {{-- Второй пункт: Загрузка на eBay --}}
    <div class="control-section">
        <h2>List Products to eBay.de</h2>

        <form action="" method="GET" target="_blank" class="upload-form">
            <div class="form-group">
                <label for="start_id">Start from Product ID:</label>
                <input type="number" name="start_id" id="start_id" placeholder="e.g. 100" required>
            </div>

            <div class="form-group">
                <label for="limit">Count of products:</label>
                <input type="number" name="limit" id="limit" placeholder="e.g. 50" required>
            </div>

            <button type="submit" class="btn-submit">Start Upload to eBay</button>
        </form>
    </div>

    <style>
        .control-section { margin-bottom: 30px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .main-link { color: #055ba5; }
        .description { font-size: 0.9em; color: #666; }
        .upload-form { display: flex; flex-direction: column; max-width: 300px; gap: 15px; margin-top: 10px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: bold; margin-bottom: 5px; }
        .btn-submit { background-color: #28a745; color: white; padding: 10px; border: none; border-radius: 4px; cursor: pointer; }
        .btn-submit:hover { background-color: #218838; }
    </style>
@endsection
