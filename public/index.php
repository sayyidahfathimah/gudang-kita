<?php

declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH.'/app/bootstrap.php';
$config = require_once BASE_PATH.'/config/app.php';
date_default_timezone_set($config['timezone']);
if ($config['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}
        else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}
try {
    require_once BASE_PATH.'/config/database.php';
} catch (Throwable $error) {
    \App\Support\Logger::error($error, 'database_connection_failed');
    http_response_code(503);
    header('Retry-After: 30');
    require_once BASE_PATH.'/views/503.php';
    exit;
}
if (session_status() === PHP_SESSION_NONE) {
    session_name('gudang_kita_session');
    session_start(['cookie_httponly' => true,'cookie_samesite' => 'Strict']);
}
$page = (string)($_GET['page'] ?? 'dashboard');
$action = (string)($_GET['action'] ?? 'index');
$id = (int)($_GET['id'] ?? 0);
$public = ['login'];
if (!in_array($page, [...$public, 'api'], true) && !isset($_SESSION['user_id'])) {
    redirect('login');
}
if (in_array($page, ['projects','tasks'], true) && !in_array((string)($_SESSION['role'] ?? ''), ['Admin','Sales'], true)) {
    http_response_code(403);
    require_once BASE_PATH.'/views/403.php';
    exit;
}
if (in_array($page, ['purchase','stock','movements'], true) && !in_array((string)($_SESSION['role'] ?? ''), ['Admin','WarehouseStaff'], true)) {
    http_response_code(403);
    require_once BASE_PATH.'/views/403.php';
    exit;
}
if ($page === 'masters' && in_array((string)($_SESSION['role'] ?? ''), ['Sales','WarehouseStaff'], true) && $action === 'catalog') {
    // Read-only active product catalog for Sales and Warehouse Staff.
} elseif ($page === 'masters' && !in_array((string)($_SESSION['role'] ?? ''), ['Admin'], true)) {
    http_response_code(403);
    require_once BASE_PATH.'/views/403.php';
    exit;
}
if ($page === 'sales' && !in_array((string)($_SESSION['role'] ?? ''), ['Admin','Sales','WarehouseStaff'], true)) {
    http_response_code(403);
    require_once BASE_PATH.'/views/403.php';
    exit;
}
$map = ['login' => \App\Controller\Auth\LoginController::class,'logout' => \App\Controller\Auth\LoginController::class,'dashboard' => \App\Controller\DashboardController::class,'users' => \App\Controller\UserController::class,'projects' => \App\Controller\ProjectController::class,'tasks' => \App\Controller\TaskController::class,'masters' => \App\Controller\InventoryMasterController::class,'stock' => \App\Controller\InventoryController::class,'movements' => \App\Controller\InventoryController::class,'purchase' => \App\Controller\PurchaseOrderController::class,'sales' => \App\Controller\SalesOrderController::class,'reports' => \App\Controller\ReportController::class,'api' => \App\Controller\ApiController::class];
if (!isset($map[$page])) {
    http_response_code(404);
    require_once BASE_PATH.'/views/404.php';
    exit;
}
try {
    $c = new $map[$page]($pdo);
    if ($page === 'api' && $action === 'availability' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $c->availability();
        exit;
    }
    if ($page === 'api') {
        $c->notFound();
        exit;
    }
    if ($page === 'login') {
        $_SERVER['REQUEST_METHOD'] === 'POST' ? $c->handle() : $c->index();
        exit;
    }
    if ($page === 'logout') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method Not Allowed');
        }$c->logout();
        exit;
    }
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $c->create();
        exit;
    }
    if ($action === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $c->store();
        exit;
    }
    if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $c->edit($id);
        exit;
    }
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $c->update($id);
        exit;
    }
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $c->delete($id);
        exit;
    }
    if ($page === 'masters' && $action === 'deactivate' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $c->deactivate($id);
        exit;
    }
    if ($action === 'archive' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $c->archive($id);
        exit;
    }
    if ($page === 'purchase' && $action === 'receive' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $c->receive($id);
        exit;
    }
    if ($action === 'status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $c->status($id);
        exit;
    }
    if ($action === 'view' && $page !== 'stock' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $c->show($id);
        exit;
    }
    if ($page === 'masters' && $action === 'index') {
        $c->index();
        exit;
    }
    if ($page === 'masters' && $action === 'catalog') {
        $c->catalog();
        exit;
    }
    if ($page === 'stock') {
        if ($action === 'view') {
            $c->product($id);
            exit;
        }
        $c->stock();
        exit;
    }
    if ($page === 'movements') {
        $c->movements();
        exit;
    }
    if ($page === 'purchase' && $action === 'index') {
        $c->index();
        exit;
    }
    if ($page === 'sales' && $action === 'index') {
        $c->index();
        exit;
    }
    if ($page === 'reports') {
        $c->index();
        exit;
    }
    if (($page === 'purchase' || $page === 'sales') && $action === 'status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $c->status($id);
        exit;
    }
    $c->index();
}
        catch (Throwable $e) {
    \App\Support\Logger::error($e);
    http_response_code(500);
    require_once BASE_PATH.'/views/500.php';
}
