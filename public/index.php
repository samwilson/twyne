<?php

require __DIR__ . '/../vendor/autoload.php';

use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\Dispatcher;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std;

// Routes are of the form: URL => [ Controller, method ]
$routes = [
    '/' => ['Home', 'home'],
    '/{file:[a-z0-9_-]+}.css' => ['Assets', 'css'],

    // Posts.
    '/new' => ['Post', 'edit'],
    '/save' => ['Post', 'save'],
    '/{id:\d+}' => ['Post', 'view'],
    '/{id:\d+}/edit' => ['Post', 'edit'],

    // Dates.
    '/dates[/{date:[c.0-9-s]+}]' => ['Dates', 'view'],

    // Users.
    '/register' => ['User', 'register'],
    '/reset' => ['User', 'resetPartOne'],
    '/reset/{token}' => ['User', 'resetPartTwo'],
    '/login' => ['User', 'login'],
    '/logout' => ['User', 'logout'],
];

$routeCollector = new RouteCollector(new Std(), new GroupCountBasedDataGenerator());
foreach ($routes as $route => $handler) {
    $routeCollector->addRoute(['GET', 'POST'], $route, $handler);
}
$dispatcher = new GroupCountBasedDispatcher($routeCollector->getData());

$httpMethod = $_SERVER['REQUEST_METHOD'];
$selfDir = dirname($_SERVER['PHP_SELF']);
$uri = '/' . trim(substr($_SERVER['REQUEST_URI'], strlen($selfDir)), '/');
$routeInfo = $dispatcher->dispatch($httpMethod, $uri);
switch ($routeInfo[0]) {
    case Dispatcher::NOT_FOUND:
        http_response_code(404);
        echo "404 Not Found";
        break;
    case Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        http_response_code(405);
        echo "405 Method Not Allowed";
        break;
    case Dispatcher::FOUND:
        $controllerClass = 'Samwilson\\Twyne\\Controller\\' . $routeInfo[1][0] . 'Controller';
        $controller = new $controllerClass($_GET, $_POST);
        $controllerMethod = $routeInfo[1][1] . ucfirst(strtolower($httpMethod));
        if (!method_exists($controller, $controllerMethod)) {
            http_response_code(405);
            echo "405 Method Not Allowed";
            break;
        }
        $controller->$controllerMethod($routeInfo[2]);
        break;
}
