<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Routing\Router;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\Response;
use Great\Tecdoc\Controllers\TecDocController;

$container = new Container();
$dispatcher = new Dispatcher();

$router = new Router($dispatcher, $container);

$router->group(['prefix' => 'tecdoc'], function ($router) {
    $router->get('/', [TecDocController::class, 'index']);
});

$request = Request::createFromGlobals();

try {
    $response = $router->dispatch($request);
} catch (NotFoundHttpException $e) {
    $response = new Response('Страница не найдена (кастомная 404)', 404);
}

echo $response->getContent();