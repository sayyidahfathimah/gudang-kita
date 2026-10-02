<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH.'/app/bootstrap.php';
$check=$_GET['check']??'live';
if($check==='live'||$check==='startup'){http_response_code(200);header('Content-Type: application/json');echo json_encode(['status'=>'ok','check'=>$check]);exit;}
try{require BASE_PATH.'/config/database.php';$pdo->query('SELECT 1');http_response_code(200);header('Content-Type: application/json');echo json_encode(['status'=>'ok','check'=>'ready']);}catch(Throwable){http_response_code(503);header('Content-Type: application/json');echo json_encode(['status'=>'unavailable','check'=>'ready']);}
