<?php

declare(strict_types=1);

namespace Tests\DatabaseIntegration;

use App\Repository\ReportRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class ReportRepositoryIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_PORT') ?: '3306', getenv('DB_DATABASE') ?: 'inventory_db');
        $this->pdo = new PDO($dsn, (string) getenv('DB_USERNAME'), (string) getenv('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    public function testSalesReportContainsOnlyOwnSalesOrders(): void
    {
        $salesId = (int) $this->pdo->query("SELECT id FROM users WHERE role='Sales' ORDER BY id LIMIT 1")->fetchColumn();
        $expected = $this->pdo->prepare('SELECT so_number FROM sales_orders WHERE created_by=? ORDER BY so_date,so_number');
        $expected->execute([$salesId]);

        $rows = (new ReportRepository($this->pdo))->orderRows('2000-01-01', '2099-12-31', $salesId);

        self::assertNotEmpty($rows);
        self::assertSame($expected->fetchAll(PDO::FETCH_COLUMN), array_column($rows, 'order_number'));
        self::assertSame(['SO'], array_values(array_unique(array_column($rows, 'order_type'))));
    }

    public function testAdminReportIncludesPurchaseAndSalesOrders(): void
    {
        $expectedCount = (int) $this->pdo->query('SELECT (SELECT COUNT(*) FROM purchase_orders) + (SELECT COUNT(*) FROM sales_orders)')->fetchColumn();
        $rows = (new ReportRepository($this->pdo))->orderRows('2000-01-01', '2099-12-31');

        self::assertSame($expectedCount, count($rows));
        self::assertContains('PO', array_column($rows, 'order_type'));
        self::assertContains('SO', array_column($rows, 'order_type'));
    }
}
