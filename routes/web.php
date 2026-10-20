<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AppUserController;
use App\Http\Controllers\AppUserAdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ApiAuthMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;

$router = $app->router();

$router->get('/', [HomeController::class, 'index'])->middleware(AuthMiddleware::class);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'])->middleware(CsrfMiddleware::class);
$router->post('/logout', [AuthController::class, 'logout'])->middleware([AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/users', [UserController::class, 'index'])->middleware([AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/users', [UserController::class, 'store'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/users/{id}/update', [UserController::class, 'update'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/users/{id}/status', [UserController::class, 'setStatus'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/content', [ContentController::class, 'index'])->middleware([AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/content', [ContentController::class, 'store'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/content/{id}/update', [ContentController::class, 'update'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/content/{id}/status', [ContentController::class, 'setStatus'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/content/{id}/delete', [ContentController::class, 'destroy'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/library', [LibraryController::class, 'index'])->middleware([AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/library', [LibraryController::class, 'store'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/library/{id}/update', [LibraryController::class, 'update'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/library/{id}/delete', [LibraryController::class, 'destroy'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/app-users', [AppUserAdminController::class, 'index'])->middleware([AuthMiddleware::class, AdminMiddleware::class]);
$router->post('/app-users', [AppUserAdminController::class, 'store'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/app-users/{id}/update', [AppUserAdminController::class, 'update'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/app-users/{id}/status', [AppUserAdminController::class, 'setStatus'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->post('/app-users/{id}/delete', [AppUserAdminController::class, 'destroy'])->middleware([AuthMiddleware::class, AdminMiddleware::class, CsrfMiddleware::class]);
$router->get('/pages/{slug}', [ContentController::class, 'show']);
$router->get('/ping', [HomeController::class, 'ping']);
$router->post('/api/register', [AppUserController::class, 'register']);
$router->post('/api/login', [AppUserController::class, 'login']);
$router->get('/api/profile', [AppUserController::class, 'profile'])->middleware(ApiAuthMiddleware::class);
$router->post('/api/logout', [AppUserController::class, 'logout'])->middleware(ApiAuthMiddleware::class);
$router->post('/api/logout-all', [AppUserController::class, 'logoutAll'])->middleware(ApiAuthMiddleware::class);
$router->put('/api/profile', [AppUserController::class, 'update'])->middleware(ApiAuthMiddleware::class);
$router->post('/api/profile', [AppUserController::class, 'update'])->middleware(ApiAuthMiddleware::class);
