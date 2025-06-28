<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class Log
{
    public static function add(string|null $traceId, string $message, int $indent) {
        if(!$traceId) {
            $date = now()->toDateTimeString();
            $logMessage = $date . ' ' . $message . PHP_EOL;

            $logPath = base_path('app/Console/Commands/logs.log');
            File::append($logPath, $logMessage);
        } else {
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
}
