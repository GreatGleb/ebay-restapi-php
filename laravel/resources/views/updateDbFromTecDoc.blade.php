@extends('layouts.app')

@section('title','Update TecDoc data in DB')
@section('content')
    <h1>Update TecDoc data in DB</h1>

    <h3>Logs</h3>
    <div class="logs-container">

    </div>

    <script>
        async function sendRequestForUpdating() {
            const url = "http://localhost/api/update/products/fromTecDoc";

            try {
                const response = await fetch(url, {
                    method: 'GET',
                });

                const result = await response.json();
                console.log(result)
            } catch (error) {
                console.log(error.message)
            }
        }

        async function sendRequestForLogs() {
            const url = "http://localhost/python/getCurrentLogs";

            try {
                const response = await fetch(url, {
                    method: 'GET',
                });

                const result = await response.json();
                console.log(result)
            } catch (error) {
                console.log(error.message)
            }
        }

        sendRequestForUpdating()
    </script>
@endsection
