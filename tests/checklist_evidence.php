<?php

declare(strict_types=1);

// Run only against the disposable acceptance database, never against a live DB.
if (getenv('DB_HOST') !== 'acceptance-db') {
    fwrite(STDERR, "Refusing to inspect a non-acceptance database.\n");
    exit(2);
}

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Repository\DashboardRepository;
use App\Repository\InventoryRepository;
use App\Repository\ReportRepository;

$pdo = new PDO(
    'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_DATABASE') . ';charset=utf8mb4',
    (string) getenv('DB_USERNAME'),
    (string) getenv('DB_PASSWORD'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$inventory = new InventoryRepository($pdo);
$reports = new ReportRepository($pdo);
$dashboard = new DashboardRepository($pdo);
$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$warehouseCount = (int) $pdo->query('SELECT COUNT(*) FROM warehouses')->fetchColumn();
$stockRows = $reports->stockRows();
assertSame($productCount * $warehouseCount, count($stockRows), 'Every product–warehouse pair exists');
$summary = $inventory->summary();
$stockTotal = array_sum(array_map(static fn (array $row): float => (float) $row['current_stock'], $stockRows));
assertSame((float) $summary['current_stock'], $stockTotal, 'Dashboard stock equals stock report total');

$breakdown = $inventory->productStockBreakdown(2);
assertSame($warehouseCount, count($breakdown['warehouses']), 'Product detail lists every warehouse');
assertSame((float) $breakdown['total_stock'], array_sum(array_map(static fn (array $row): float => (float) $row['current_stock'], $breakdown['warehouses'])), 'Product detail total equals warehouse sum');
assertSame(true, count(array_unique(array_column($breakdown['warehouses'], 'current_stock'))) > 1, 'Sample product has different balances in two warehouses');

$from = '2000-01-01';
$to = '2100-01-01';
$orders = $reports->orderRows($from, $to);
$counts = $dashboard->orderCounts();
foreach (['PO' => 'purchase', 'SO' => 'sales'] as $type => $key) {
    foreach ($counts[$key] as $status => $count) {
        $matching = array_filter($orders, static fn (array $row): bool => $row['order_type'] === $type && $row['status'] === $status);
        assertSame($count, count($matching), "Dashboard {$type} {$status} equals CSV rows");
    }
}

$purchaseMonthly = $reports->purchaseMonthly($from, $to);
$salesMonthly = $reports->salesMonthly($from, $to);
assertSame(count(array_filter($orders, static fn (array $row): bool => $row['order_type'] === 'PO' && $row['status'] === 'Received')), array_sum(array_map(static fn (array $row): int => (int) $row['orders'], $purchaseMonthly)), 'Purchase monthly counts received PO only');
assertSame(count(array_filter($orders, static fn (array $row): bool => $row['order_type'] === 'SO' && $row['status'] === 'Fulfilled')), array_sum(array_map(static fn (array $row): int => (int) $row['orders'], $salesMonthly)), 'Sales monthly counts fulfilled SO only');

$movements = $reports->movementRows($from, $to);
assertSame((int) $pdo->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn(), count($movements), 'Full date range includes every ledger row');
assertSame(0, count($reports->orderRows('2000-01-01', '2000-01-31')), 'Empty date range has no orders');
echo "PASS: {$productCount} products × {$warehouseCount} warehouses = " . count($stockRows) . " stock rows; dashboard/CSV/order status/ledger totals agree.\n";

function assertSame(mixed $expected, mixed $actual, string $message): void
{
    if ($expected !== $actual) {
        throw new RuntimeException("{$message}: expected " . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
    echo "PASS: {$message}\n";
}
