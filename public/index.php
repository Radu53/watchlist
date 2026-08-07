<?php

declare(strict_types=1);
session_start();

$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Controllers/HomeController.php';
require_once __DIR__ . '/../app/Controllers/MediaController.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/AdminController.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\MediaController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;

$router = new Router($config['app']['base_url'] ?? '');

$router->get('/', [HomeController::class, 'index']);
$router->get('/categories', [HomeController::class, 'categories']);
$router->get('/media/create', [AdminController::class, 'mediaCreate']);
$router->post('/media/store', [MediaController::class, 'store']);
$router->get('/media/todo', [AdminController::class, 'mediaTodo']);
$router->get('/media/edit', [AdminController::class, 'mediaEdit']);
$router->post('/media/update', [MediaController::class, 'update']);
$router->post('/media/delete', [MediaController::class, 'delete']);
$router->post('/media/status', [MediaController::class, 'changeStatus']);
$router->post('/media/watch', [MediaController::class, 'watch']);
$router->post('/media/rate', [MediaController::class, 'rate']);
$router->post('/media/parse', [MediaController::class, 'parse']);
$router->get('/history', [MediaController::class, 'history']);
$router->get('/genres/search', [MediaController::class, 'searchGenres']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);
$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/parser-rules', [AdminController::class, 'parserRules']);
$router->post('/admin/parser-rules', [AdminController::class, 'saveParserRule']);
$router->post('/admin/parser-rules/delete', [AdminController::class, 'deleteParserRule']);
$router->get('/admin/media/create', [AdminController::class, 'mediaCreate']);
$router->get('/admin/media/todo', [AdminController::class, 'mediaTodo']);
$router->get('/admin/media/edit', [AdminController::class, 'mediaEdit']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);