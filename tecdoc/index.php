<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Great\Tecdoc\Controllers\TecDocController;
use Great\Tecdoc\Controllers\UseTecDocController;

$app = new Container;
Facade::setFacadeApplication($app);

$app->singleton('request', function() {
    return Request::capture();
});

$app->singleton('router', function($app) {
    return new Router(new Illuminate\Events\Dispatcher, $app);
});

$router = $app->make('router');

$router->group(['prefix' => 'tecdoc'], function ($router) {
    $router->get('/', [TecDocController::class, 'index']);
    $router->post('/products-info', [UseTecDocController::class, 'getProductsInfo']);
    $router->get('/{reference}', [UseTecDocController::class, 'testGetProductInfo']);
});

$request = $app->make('request');

try {
    $response = $router->dispatch($request);
} catch (NotFoundHttpException $e) {
    $response = new Response('Страница не найдена (кастомная 404)', 404);
}

echo $response->send();