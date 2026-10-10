<?php

declare(strict_types=1);

use App\Core\Application;
use App\Core\Container;
use App\Core\Env;
use App\Core\Router;
use App\Core\Session;

require_once __DIR__ . '/../vendor/autoload.php';

$basePath = dirname(__DIR__);
Env::load($basePath . '/.env');

$config = [
    'app' => require $basePath . '/config/app.php',
    'database' => require $basePath . '/config/database.php',
];

$container = new Container();
$router = new Router();
$session = new Session();
$session->start();

$container->singleton('config', static fn () => $config);
$container->singleton(Session::class, static fn () => $session);

$app = new Application($basePath, $container, $router);
$GLOBALS['app'] = $app;

require $basePath . '/routes/web.php';

return $app;
