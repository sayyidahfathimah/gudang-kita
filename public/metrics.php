<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH.'/app/bootstrap.php';
header('Content-Type: text/plain; version=0.0.4');
echo "# HELP gudang_kita_up Application process health\n# TYPE gudang_kita_up gauge\ngudang_kita_up 1\n";
try{require BASE_PATH.'/config/database.php';$pdo->query('SELECT 1');echo "# HELP gudang_kita_database_up Database connectivity\n# TYPE gudang_kita_database_up gauge\ngudang_kita_database_up 1\n";foreach(['products','customers','purchase_orders','sales_orders'] as $table){$value=(int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();echo 'gudang_kita_records{table="'.$table.'"} '.$value."\n";}}catch(Throwable){echo "gudang_kita_database_up 0\n";}
