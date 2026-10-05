<?php

declare(strict_types=1);

namespace App\Repository;

final class ReportRepository
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function stockRows(): array
    {
        return $this->pdo->query('SELECT p.code,p.name,w.name warehouse_name,s.current_stock,p.minimum_stock FROM stocks s JOIN products p ON p.id=s.product_id JOIN warehouses w ON w.id=s.warehouse_id ORDER BY p.name,w.name')->fetchAll();
    }

    public function purchaseMonthly(string $from, string $to): array
    {
        $statement = $this->pdo->prepare("SELECT DATE_FORMAT(po_date,'%Y-%m') period,COUNT(*) orders,COALESCE(SUM(total_amount),0) total FROM purchase_orders WHERE status='Received' AND po_date BETWEEN ? AND ? GROUP BY DATE_FORMAT(po_date,'%Y-%m') ORDER BY period DESC LIMIT 12");
        $statement->execute([$from, $to]);
        return $statement->fetchAll();
    }

    public function salesMonthly(string $from, string $to, ?int $salesId = null): array
    {
        $scope = $salesId === null ? '' : ' AND created_by = ?';
        $statement = $this->pdo->prepare("SELECT DATE_FORMAT(so_date,'%Y-%m') period,COUNT(*) orders,COALESCE(SUM(total_amount),0) total FROM sales_orders WHERE status='Fulfilled' AND so_date BETWEEN ? AND ?{$scope} GROUP BY DATE_FORMAT(so_date,'%Y-%m') ORDER BY period DESC LIMIT 12");
        $statement->execute($salesId === null ? [$from, $to] : [$from, $to, $salesId]);
        return $statement->fetchAll();
    }

    public function orderRows(string $from, string $to, ?int $salesId = null): array
    {
        if ($salesId !== null) {
            $statement = $this->pdo->prepare("SELECT 'SO' order_type,so.so_number order_number,so.so_date order_date,so.status,c.name party,so.total_amount FROM sales_orders so JOIN customers c ON c.id=so.customer_id WHERE so.so_date BETWEEN ? AND ? AND so.created_by = ? ORDER BY so.so_date,so.so_number");
            $statement->execute([$from, $to, $salesId]);
            return $statement->fetchAll();
        }

        $statement = $this->pdo->prepare("SELECT 'PO' order_type,po.po_number order_number,po.po_date order_date,po.status,s.name party,po.total_amount FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id WHERE po.po_date BETWEEN ? AND ? UNION ALL SELECT 'SO',so.so_number,so.so_date,so.status,c.name,so.total_amount FROM sales_orders so JOIN customers c ON c.id=so.customer_id WHERE so.so_date BETWEEN ? AND ? ORDER BY order_date,order_number");
        $statement->execute([$from, $to, $from, $to]);
        return $statement->fetchAll();
    }

    public function movementRows(string $from, string $to): array
    {
        $statement = $this->pdo->prepare('SELECT m.transaction_type,m.adjustment_direction,m.transaction_number,m.movement_date,p.code product_code,p.name product_name,w.name warehouse_name,m.qty,m.note FROM stock_movements m JOIN products p ON p.id=m.product_id JOIN warehouses w ON w.id=m.warehouse_id WHERE m.movement_date >= ? AND m.movement_date < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY m.movement_date,m.id');
        $statement->execute([$from, $to]);
        return $statement->fetchAll();
    }
}
