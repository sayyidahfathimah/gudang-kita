<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/config/database.php';

$statement = $pdo->query('SELECT p.code, p.name, p.minimum_stock, COALESCE(SUM(s.current_stock), 0) AS current_stock FROM products p LEFT JOIN stocks s ON s.product_id = p.id WHERE p.status = "Active" GROUP BY p.id HAVING current_stock < p.minimum_stock ORDER BY current_stock ASC');
$rows = $statement->fetchAll();
echo "Produk stok rendah\n";
foreach ($rows as $row) {
    echo sprintf("- %s | %s: %s / minimum %s\n", $row['code'], $row['name'], $row['current_stock'], $row['minimum_stock']);
}
echo 'Total: ' . count($rows) . PHP_EOL;
