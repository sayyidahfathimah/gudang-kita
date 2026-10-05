<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StockMovementType;
use App\Contract\SalesOrderRepositoryInterface;

final class SalesOrderRepository implements SalesOrderRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function list(array $filters = [], ?int $createdBy = null, int $page = 1, int $perPage = 10): array
    {
        $sql = 'SELECT so.*, c.name AS customer_name, w.name AS warehouse_name, creator.name AS creator_name
            FROM sales_orders so
            JOIN customers c ON c.id = so.customer_id
            JOIN warehouses w ON w.id = so.warehouse_id
            LEFT JOIN users creator ON creator.id = so.created_by';
        $where = [];
        $params = [];
        if ($createdBy !== null) {
            $where[] = 'so.created_by = ?';
            $params[] = $createdBy;
        }
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(so.so_number LIKE ? OR c.name LIKE ?)';
            $params[] = '%'.$search.'%';
            $params[] = '%'.$search.'%';
        }
        $status = (string) ($filters['status'] ?? '');
        if ($status !== '' && in_array($status, ['Draft','PendingApproval','Approved','Fulfilled','Cancelled'], true)) {
            $where[] = 'so.status = ?';
            $params[] = $status;
        }
        $from = ' FROM sales_orders so JOIN customers c ON c.id=so.customer_id JOIN warehouses w ON w.id=so.warehouse_id LEFT JOIN users creator ON creator.id=so.created_by';
        $whereSql = $where ? ' WHERE '.implode(' AND ', $where) : '';
        $sort = ($filters['sort'] ?? '') === 'date_asc' ? 'so.so_date ASC,so.id ASC' : 'so.so_date DESC,so.id DESC';
        $count = $this->pdo->prepare('SELECT COUNT(*)'.$from.$whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $statement = $this->pdo->prepare('SELECT so.*,c.name customer_name,w.name warehouse_name,creator.name creator_name'.$from.$whereSql." ORDER BY {$sort} LIMIT {$perPage} OFFSET {$offset}");
        $statement->execute($params);
        return ['items' => $statement->fetchAll(),'total' => $total,'page' => $page,'per_page' => $perPage,'pages' => $pages];
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT so.*, c.name AS customer_name, w.name AS warehouse_name
            FROM sales_orders so JOIN customers c ON c.id = so.customer_id
            JOIN warehouses w ON w.id = so.warehouse_id WHERE so.id = ?');
        $statement->execute([$id]);
        $order = $statement->fetch();
        if (!$order) {
            return null;
        }
        $details = $this->pdo->prepare('SELECT d.*, p.code product_code, p.name product_name, p.unit
            FROM sales_order_details d JOIN products p ON p.id = d.product_id WHERE d.sales_order_id = ? ORDER BY d.id');
        $details->execute([$id]);
        $order['details'] = $details->fetchAll();
        return $order;
    }

    public function create(array $data, array $details, int $createdBy): int
    {
        $this->pdo->beginTransaction();
        try {
            $number = 'SO-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $statement = $this->pdo->prepare('INSERT INTO sales_orders(so_number,customer_id,created_by,warehouse_id,so_date,status,notes,total_amount) VALUES(?,?,?,?,?,?,?,0)');
            $statement->execute([$number, $data['customer_id'], $createdBy, $data['warehouse_id'], $data['so_date'], 'Draft', $data['notes']]);
            $id = (int) $this->pdo->lastInsertId();
            $total = 0.0;
            foreach ($details as $item) {
                $subtotal = $item['qty'] * $item['price'];
                $this->pdo->prepare('INSERT INTO sales_order_details(sales_order_id,product_id,qty,price,subtotal) VALUES(?,?,?,?,?)')->execute([$id, $item['product_id'], $item['qty'], $item['price'], $subtotal]);
                $total += $subtotal;
            }
            $this->pdo->prepare('UPDATE sales_orders SET total_amount = ? WHERE id = ?')->execute([$total, $id]);
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }

    public function transition(int $id, string $target, int $actorId, string $role): void
    {
        $this->pdo->beginTransaction();
        try {
            $order = $this->lock($id);
            if (!$order) {
                throw new \DomainException('Sales Order tidak ditemukan.');
            }
            $current = $order['status'];
            if ($target === 'PendingApproval' && $role === 'Sales' && (int) $order['created_by'] === $actorId && $current === 'Draft') {
                $this->pdo->prepare("UPDATE sales_orders SET status = 'PendingApproval' WHERE id = ?")->execute([$id]);
            } elseif ($target === 'Approved' && $role === 'Admin' && $current === 'PendingApproval') {
                $this->pdo->prepare("UPDATE sales_orders SET status = 'Approved', approved_by = ? WHERE id = ?")->execute([$actorId, $id]);
            } elseif ($target === 'Cancelled' && (($role === 'Sales' && (int) $order['created_by'] === $actorId && $current === 'Draft') || ($role === 'Admin' && in_array($current, ['Draft', 'PendingApproval', 'Approved'], true)))) {
                $this->pdo->prepare("UPDATE sales_orders SET status = 'Cancelled' WHERE id = ?")->execute([$id]);
            } elseif ($target === 'Fulfilled' && $role === 'WarehouseStaff' && $current === 'Approved') {
                $this->issueStock($order, $actorId);
                $this->pdo->prepare("UPDATE sales_orders SET status = 'Fulfilled' WHERE id = ?")->execute([$id]);
            } else {
                throw new \DomainException('Transisi status tidak diizinkan untuk peran ini.');
            }
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function lock(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM sales_orders WHERE id = ? FOR UPDATE');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    private function issueStock(array $order, int $actorId): void
    {
        $details = $this->pdo->prepare('SELECT d.*, p.name FROM sales_order_details d JOIN products p ON p.id = d.product_id WHERE d.sales_order_id = ?');
        $details->execute([$order['id']]);
        foreach ($details->fetchAll() as $detail) {
            $stock = $this->pdo->prepare('SELECT id, current_stock FROM stocks WHERE product_id = ? AND warehouse_id = ? FOR UPDATE');
            $stock->execute([$detail['product_id'], $order['warehouse_id']]);
            $row = $stock->fetch();
            if (!$row || (float) $row['current_stock'] < (float) $detail['qty']) {
                throw new \DomainException('Stok ' . $detail['name'] . ' tidak mencukupi.');
            }
            $this->pdo->prepare('UPDATE stocks SET stock_out = stock_out + ?, current_stock = current_stock - ? WHERE id = ?')->execute([$detail['qty'], $detail['qty'], $row['id']]);
            $this->pdo->prepare('INSERT INTO stock_movements(transaction_type,transaction_id,transaction_number,product_id,warehouse_id,qty,created_by,movement_date) VALUES(?,?,?,?,?,?,?,NOW())')->execute([StockMovementType::Issue->value, $order['id'], $order['so_number'], $detail['product_id'], $order['warehouse_id'], $detail['qty'], $actorId]);
        }
    }
}
