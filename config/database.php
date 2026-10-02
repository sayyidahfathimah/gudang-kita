<?php
declare(strict_types=1);
$host = envv('DB_HOST','db');
$port = envv('DB_PORT','3306');
$name = envv('DB_DATABASE','inventory_db');
$user = envv('DB_USERNAME','appuser');
$pass = envv('DB_PASSWORD','');
$dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [
 PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
 PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
 PDO::ATTR_EMULATE_PREPARES=>false,
]);
