<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;

$router = $app->router();

$router->get('/', [HomeController::class, 'index'])->middleware(AuthMiddleware::class);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'])->middleware(CsrfMiddleware::class);
$router->post('/logout', [AuthController::class, 'logout'])->middleware([AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/users', [UserController::class, 'index'])->middleware([AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/users', [UserController::class, 'store'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/ping', [HomeController::class, 'ping']);
