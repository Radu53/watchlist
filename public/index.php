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

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\MediaController;
use App\Controllers\AuthController;

$router = new Router($config['app']['base_url'] ?? '');

$router->get('/', [HomeController::class, 'index']);
$router->get('/media/create', [MediaController::class, 'create']);
$router->post('/media/store', [MediaController::class, 'store']);
$router->get('/media/todo', [MediaController::class, 'todo']);
$router->get('/media/edit', [MediaController::class, 'edit']);
$router->post('/media/update', [MediaController::class, 'update']);
$router->post('/media/status', [MediaController::class, 'changeStatus']);
$router->post('/media/watch', [MediaController::class, 'watch']);
$router->post('/media/parse', [MediaController::class, 'parse']);
$router->get('/history', [MediaController::class, 'history']);
$router->get('/genres/search', [MediaController::class, 'searchGenres']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);