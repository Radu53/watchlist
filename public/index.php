<?php

declare(strict_types=1);

$config = require __DIR__ . '/../config/config.php';

require_once __DIR__ . '/../app/Core/helpers.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/View.php';
require_once __DIR__ . '/../app/Core/Router.php';
require_once __DIR__ . '/../app/Controllers/HomeController.php';
require_once __DIR__ . '/../app/Controllers/MediaController.php';

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\MediaController;

$router = new Router($config['app']['base_url'] ?? '');

$router->get('/', [HomeController::class, 'index']);
$router->get('/media/create', [MediaController::class, 'create']);
$router->post('/media/store', [MediaController::class, 'store']);
$router->get('/media/todo', [MediaController::class, 'todo']);
$router->get('/media/edit', [MediaController::class, 'edit']);
$router->post('/media/update', [MediaController::class, 'update']);
$router->post('/media/status', [MediaController::class, 'changeStatus']);
$router->post('/media/watch', [MediaController::class, 'watch']);
$router->get('/history', [MediaController::class, 'history']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);