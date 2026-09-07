<?php
date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/vendor/autoload.php';

use App\Routing\Router;

$request_state    = isset($_POST['request_state']) ? trim($_POST['request_state']) : '';
$request_function = isset($_POST['request_function']) ? trim($_POST['request_function']) : '';

$router = new Router();

$routes = [
    'login' => [
        'login' => __DIR__ . '/core/mainLogin/Login.php',
    ],
];

foreach ($routes as $state => $actions) {
    foreach ($actions as $action => $file) {
        $router->post($state, $action, $file);
    }
}

$router->dispatch($request_state, $request_function);
