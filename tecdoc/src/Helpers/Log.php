<?php

namespace Great\Tecdoc\Helpers;

use Illuminate\Support\Facades\Http;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Log
{
    private static $logger = null;

    private static function getLogger(): Logger
    {
        if (!self::$logger) {
            self::$logger = new Logger('tecdoc');
            self::$logger->pushHandler(new StreamHandler('/var/www/tecdoc/storage/logs/tecdoc.log'));
        }
        return self::$logger;
    }

    public static function add($traceId, string $message, int $indent) {
        self::getLogger()->info($message);

        if (!$traceId) {
            return true;
        }

        try {
            Http::timeout(5)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ])->post("http://ebay_restapi_nginx/logs/add", [
                    'source' => 'Tecdoc',
                    'trace_id' => $traceId,
                    'message' => $message,
                    'indent' => $indent,
                ]);
        } catch (\Exception $e) {
            self::getLogger()->warning('log-service unavailable: ' . $e->getMessage());
        }

        return true;
    }
}