<?php

declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH.'/app/bootstrap.php';
$jsonHeader = 'Content-Type: application/json';
$check = $_GET['check'] ?? 'live';
if ($check === 'live' || $check === 'startup') {
    http_response_code(200);
    header($jsonHeader);
    echo json_encode(['status' => 'ok','check' => $check]);
    exit;
}
try {
    require_once BASE_PATH.'/config/database.php';
    $pdo->query('SELECT 1');
    http_response_code(200);
    header($jsonHeader);
    echo json_encode(['status' => 'ok','check' => 'ready']);
}
        catch (Throwable) {
    http_response_code(503);
    header($jsonHeader);
    echo json_encode(['status' => 'unavailable','check' => 'ready']);
}
