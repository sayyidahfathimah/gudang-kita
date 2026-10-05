<?php

declare(strict_types=1);

// Adds two reversible training examples without modifying existing orders.
// Run explicitly with ALLOW_DEMO_DATA=1 on a backed-up training database.
if (PHP_SAPI !== 'cli' || getenv('ALLOW_DEMO_DATA') !== '1') {
    fwrite(STDERR, "Hanya untuk data latihan. Set ALLOW_DEMO_DATA=1.\n");
    exit(1);
}

require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../config/database.php';

use App\Repository\PurchaseOrderRepository;
use App\Repository\SalesOrderRepository;
use App\Service\PurchaseOrderService;
use App\Service\SalesOrderService;

function oneId(PDO $pdo, string $sql): int
{
    $id = (int) $pdo->query($sql)->fetchColumn();
    if ($id < 1) {
        throw new RuntimeException('Data master atau user untuk contoh belum tersedia.');
    }
    return $id;
}

$supplierId = oneId($pdo, "SELECT id FROM suppliers WHERE status='Active' ORDER BY id LIMIT 1");
$customerId = oneId($pdo, "SELECT id FROM customers WHERE status='Active' ORDER BY id LIMIT 1");
$warehouseId = oneId($pdo, "SELECT id FROM warehouses WHERE status='Active' ORDER BY id LIMIT 1");
$product = $pdo->query("SELECT id,purchase_price,selling_price FROM products WHERE status='Active' ORDER BY id LIMIT 1")->fetch();
if (!$product) {
    throw new RuntimeException('Produk aktif untuk contoh belum tersedia.');
}
$productId = (int) $product['id'];
$salesId = oneId($pdo, "SELECT id FROM users WHERE username='sales02' AND role='Sales' AND is_active=1 LIMIT 1");
$warehouseUserId = oneId($pdo, "SELECT id FROM users WHERE role='WarehouseStaff' AND is_active=1 ORDER BY id LIMIT 1");

$poNote = 'Intermediate demo: penerimaan sebagian';
$findPo = $pdo->prepare('SELECT id FROM purchase_orders WHERE notes=? ORDER BY id LIMIT 1');
$findPo->execute([$poNote]);
$poId = (int) $findPo->fetchColumn();
if ($poId === 0) {
    $po = new PurchaseOrderService(new PurchaseOrderRepository($pdo));
    $poId = $po->create([
        'supplier_id' => $supplierId,
        'warehouse_id' => $warehouseId,
        'po_date' => date('Y-m-d'),
        'notes' => $poNote,
    ], [['product_id' => $productId, 'qty' => 5, 'price' => (float) $product['purchase_price']]]);
    $po->transition($poId, 'Ordered', 'WarehouseStaff');
    $details = (new PurchaseOrderRepository($pdo))->find($poId)['details'];
    $po->receive($poId, [(int) $details[0]['id'] => 2], $warehouseUserId);
}

$soNote = 'Intermediate demo: menunggu persetujuan Admin';
$findSo = $pdo->prepare('SELECT id FROM sales_orders WHERE notes=? ORDER BY id LIMIT 1');
$findSo->execute([$soNote]);
$soId = (int) $findSo->fetchColumn();
if ($soId === 0) {
    $so = new SalesOrderService(new SalesOrderRepository($pdo));
    $soId = $so->create([
        'customer_id' => $customerId,
        'warehouse_id' => $warehouseId,
        'so_date' => date('Y-m-d'),
        'notes' => $soNote,
    ], [['product_id' => $productId, 'qty' => 1, 'price' => (float) $product['selling_price']]], $salesId);
    $so->transition($soId, 'PendingApproval', $salesId, 'Sales');
}

echo "Contoh PO {$poId}: PartiallyReceived; SO {$soId}: PendingApproval.\n";
