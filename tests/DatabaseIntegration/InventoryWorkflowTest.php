<?php

declare(strict_types=1);

namespace Tests\DatabaseIntegration;

use App\Repository\PurchaseOrderRepository;
use App\Repository\SalesOrderRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class InventoryWorkflowTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            getenv('DB_HOST'),
            getenv('DB_PORT') ?: '3306',
            getenv('DB_DATABASE') ?: 'inventory_db'
        );
        $this->pdo = new PDO($dsn, (string) getenv('DB_USERNAME'), (string) getenv('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function testPurchaseOrderPartialReceiptUpdatesStockAndMovement(): void
    {
        $repository = new PurchaseOrderRepository($this->pdo);
        $productId = $this->id('products');
        $warehouseId = $this->id('warehouses');
        $before = $this->stock($productId, $warehouseId);
        $orderId = $repository->create([
            'supplier_id' => $this->id('suppliers'),
            'warehouse_id' => $warehouseId,
            'po_date' => date('Y-m-d'),
            'notes' => 'Integration test partial receipt',
        ], [['product_id' => $productId, 'qty' => 4, 'price' => 1000]]);

        $repository->transition($orderId, 'Ordered', 'Admin');
        $detail = $repository->find($orderId)['details'][0];
        $warehouseUserId = $this->userId('WarehouseStaff');
        $repository->receive($orderId, [(int) $detail['id'] => 1.5], $warehouseUserId);
        $order = $repository->find($orderId);

        self::assertSame('PartiallyReceived', $order['status']);
        self::assertSame(1.5, (float) $order['details'][0]['received_qty']);
        self::assertSame($before + 1.5, $this->stock($productId, $warehouseId));
        self::assertSame(1, $this->movementCount('Receipt', $orderId));
        self::assertSame($warehouseUserId, $this->movementActorByType('Receipt', $orderId));
    }

    public function testWarehouseStaffCanOrderDraftPurchaseOrder(): void
    {
        $repository = new PurchaseOrderRepository($this->pdo);
        $orderId = $repository->create([
            'supplier_id' => $this->id('suppliers'),
            'warehouse_id' => $this->id('warehouses'),
            'po_date' => date('Y-m-d'),
            'notes' => 'Integration test warehouse ordering',
        ], [['product_id' => $this->id('products'), 'qty' => 2, 'price' => 1000]]);

        $repository->transition($orderId, 'Ordered', 'WarehouseStaff');

        self::assertSame('Ordered', $repository->find($orderId)['status']);
    }

    public function testSalesOrderRequiresSalesSubmissionAndAdminApproval(): void
    {
        [$repository, $orderId, $salesId, $adminId] = $this->draftSalesOrder();
        $repository->transition($orderId, 'PendingApproval', $salesId, 'Sales');
        $repository->transition($orderId, 'Approved', $adminId, 'Admin');
        $order = $repository->find($orderId);

        self::assertSame('Approved', $order['status']);
        self::assertSame($adminId, (int) $order['approved_by']);
    }

    public function testWarehouseGoodsIssueReducesStockAndCreatesOutMovement(): void
    {
        [$repository, $orderId, $salesId, $adminId, $warehouseId, $productId] = $this->draftSalesOrder();
        $warehouseUserId = $this->userId('WarehouseStaff');
        $before = $this->stock($productId, $warehouseId);
        $repository->transition($orderId, 'PendingApproval', $salesId, 'Sales');
        $repository->transition($orderId, 'Approved', $adminId, 'Admin');
        $repository->transition($orderId, 'Fulfilled', $warehouseUserId, 'WarehouseStaff');

        self::assertSame('Fulfilled', $repository->find($orderId)['status']);
        self::assertSame($before - 1, $this->stock($productId, $warehouseId));
        self::assertSame(1, $this->movementCount('Issue', $orderId));
        self::assertSame($warehouseUserId, $this->movementActor($orderId));
    }

    public function testSecondGoodsIssueIsRejectedWhenFirstIssueConsumesAvailableStock(): void
    {
        $warehouseId = $this->id('warehouses');
        $productId = $this->id('products');
        $this->pdo->prepare('UPDATE stocks SET stock_in=1,stock_out=0,current_stock=1 WHERE product_id=? AND warehouse_id=?')->execute([$productId, $warehouseId]);

        [$firstRepository, $firstId, $salesId, $adminId] = $this->draftSalesOrderFor($productId, $warehouseId);
        [$secondRepository, $secondId] = $this->draftSalesOrderFor($productId, $warehouseId, $salesId);
        foreach ([[$firstRepository, $firstId], [$secondRepository, $secondId]] as [$repository, $orderId]) {
            $repository->transition($orderId, 'PendingApproval', $salesId, 'Sales');
            $repository->transition($orderId, 'Approved', $adminId, 'Admin');
        }

        $firstRepository->transition($firstId, 'Fulfilled', $this->userId('WarehouseStaff'), 'WarehouseStaff');
        try {
            $secondRepository->transition($secondId, 'Fulfilled', $this->userId('WarehouseStaff'), 'WarehouseStaff');
            self::fail('Pengeluaran kedua harus gagal karena stok sudah habis.');
        } catch (\DomainException $exception) {
            self::assertStringContainsString('tidak mencukupi', $exception->getMessage());
        }

        self::assertSame(0.0, $this->stock($productId, $warehouseId));
        self::assertSame(1, $this->movementCount('Issue', $firstId));
        self::assertSame(0, $this->movementCount('Issue', $secondId));
        self::assertSame('Approved', $secondRepository->find($secondId)['status']);
    }

    private function draftSalesOrder(): array
    {
        $repository = new SalesOrderRepository($this->pdo);
        $salesId = $this->userId('Sales');
        $warehouseId = $this->id('warehouses');
        $productId = $this->id('products');
        $orderId = $repository->create([
            'customer_id' => $this->id('customers'),
            'warehouse_id' => $warehouseId,
            'so_date' => date('Y-m-d'),
            'notes' => 'Integration test sales flow',
        ], [['product_id' => $productId, 'qty' => 1, 'price' => 2000]], $salesId);
        return [$repository, $orderId, $salesId, $this->userId('Admin'), $warehouseId, $productId];
    }

    private function draftSalesOrderFor(int $productId, int $warehouseId, ?int $salesId = null): array
    {
        $repository = new SalesOrderRepository($this->pdo);
        $salesId ??= $this->userId('Sales');
        $orderId = $repository->create([
            'customer_id' => $this->id('customers'),
            'warehouse_id' => $warehouseId,
            'so_date' => date('Y-m-d'),
            'notes' => 'Integration test controlled stock race scenario',
        ], [['product_id' => $productId, 'qty' => 1, 'price' => 2000]], $salesId);
        return [$repository, $orderId, $salesId, $this->userId('Admin')];
    }

    private function id(string $table): int
    {
        return (int) $this->pdo->query("SELECT id FROM {$table} ORDER BY id LIMIT 1")->fetchColumn();
    }

    private function userId(string $role): int
    {
        $statement = $this->pdo->prepare('SELECT id FROM users WHERE role = ? ORDER BY id LIMIT 1');
        $statement->execute([$role]);
        return (int) $statement->fetchColumn();
    }

    private function stock(int $productId, int $warehouseId): float
    {
        $statement = $this->pdo->prepare('SELECT current_stock FROM stocks WHERE product_id = ? AND warehouse_id = ?');
        $statement->execute([$productId, $warehouseId]);
        return (float) $statement->fetchColumn();
    }

    private function movementCount(string $type, int $transactionId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM stock_movements WHERE transaction_type = ? AND transaction_id = ?');
        $statement->execute([$type, $transactionId]);
        return (int) $statement->fetchColumn();
    }

    private function movementActor(int $transactionId): int
    {
        return $this->movementActorByType('Issue', $transactionId);
    }

    private function movementActorByType(string $type, int $transactionId): int
    {
        $statement = $this->pdo->prepare('SELECT created_by FROM stock_movements WHERE transaction_type = ? AND transaction_id = ?');
        $statement->execute([$type, $transactionId]);
        return (int) $statement->fetchColumn();
    }
}
