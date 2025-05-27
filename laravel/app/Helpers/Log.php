<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class Log
{
    public static function add(string $traceId, string $message, int $indent) {
        $request = [
            'source' => 'Laravel',
            'trace_id' => $traceId,
            'message' => $message,
            'indent' => $indent,
        ];

        $url = "http://ebay_restapi_nginx/logs/add";

        Http::timeout(300)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post($url, $request);
    }
}
