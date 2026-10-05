<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StockMovementType;
use App\Contract\PurchaseOrderWorkflowRepositoryInterface;

final class PurchaseOrderRepository implements PurchaseOrderWorkflowRepositoryInterface
{
    public function __construct(private \PDO $pdo)
    {
    }

    public function list(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $where = [];
        $params = [];
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $where[] = '(po.po_number LIKE ? OR s.name LIKE ?)';
            $params[] = '%'.$search.'%';
            $params[] = '%'.$search.'%';
        }
        $status = (string) ($filters['status'] ?? '');
        if ($status !== '' && in_array($status, ['Draft','Ordered','PartiallyReceived','Received','Cancelled'], true)) {
            $where[] = 'po.status = ?';
            $params[] = $status;
        }
        $from = ' FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id JOIN warehouses w ON w.id=po.warehouse_id';
        $whereSql = $where ? ' WHERE '.implode(' AND ', $where) : '';
        $sort = ($filters['sort'] ?? '') === 'date_asc' ? 'po.po_date ASC,po.id ASC' : 'po.po_date DESC,po.id DESC';
        $count = $this->pdo->prepare('SELECT COUNT(*)'.$from.$whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $statement = $this->pdo->prepare('SELECT po.*,s.name supplier_name,w.name warehouse_name'.$from.$whereSql." ORDER BY {$sort} LIMIT {$perPage} OFFSET {$offset}");
        $statement->execute($params);
        return ['items' => $statement->fetchAll(),'total' => $total,'page' => $page,'per_page' => $perPage,'pages' => $pages];
    }

    public function find(int $id, bool $forUpdate = false): ?array
    {
        $suffix = $forUpdate ? ' FOR UPDATE' : '';
        $statement = $this->pdo->prepare('SELECT po.*, s.name AS supplier_name, w.name AS warehouse_name FROM purchase_orders po JOIN suppliers s ON s.id = po.supplier_id JOIN warehouses w ON w.id = po.warehouse_id WHERE po.id = ?' . $suffix);
        $statement->execute([$id]);
        $order = $statement->fetch();
        if (!$order) {
            return null;
        }
        $details = $this->pdo->prepare('SELECT d.*, p.code product_code, p.name product_name, p.unit FROM purchase_order_details d JOIN products p ON p.id = d.product_id WHERE d.purchase_order_id = ? ORDER BY d.id' . $suffix);
        $details->execute([$id]);
        $order['details'] = $details->fetchAll();
        return $order;
    }

    public function create(array $data, array $details): int
    {
        $this->pdo->beginTransaction();
        try {
            $number = 'PO-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $statement = $this->pdo->prepare('INSERT INTO purchase_orders(po_number,supplier_id,warehouse_id,po_date,status,notes,total_amount) VALUES(?,?,?,?,?,?,0)');
            $statement->execute([$number, $data['supplier_id'], $data['warehouse_id'], $data['po_date'], 'Draft', $data['notes']]);
            $id = (int) $this->pdo->lastInsertId();
            $total = 0.0;
            foreach ($details as $item) {
                $subtotal = $item['qty'] * $item['price'];
                $this->pdo->prepare('INSERT INTO purchase_order_details(purchase_order_id,product_id,qty,received_qty,price,subtotal) VALUES(?,?,?,0,?,?)')->execute([$id, $item['product_id'], $item['qty'], $item['price'], $subtotal]);
                $total += $subtotal;
            }
            $this->pdo->prepare('UPDATE purchase_orders SET total_amount = ? WHERE id = ?')->execute([$total, $id]);
            $this->pdo->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    public function transition(int $id, string $target, string $role): void
    {
        $this->pdo->beginTransaction();
        try {
            $order = $this->find($id, true);
            if (!$order) {
                throw new \DomainException('PO tidak ditemukan.');
            }
            $current = $order['status'];
            $allowed = (in_array($role, ['Admin', 'WarehouseStaff'], true) && $current === 'Draft' && in_array($target, ['Ordered', 'Cancelled'], true));
            if (!$allowed) {
                throw new \DomainException('Transisi PO tidak diizinkan untuk peran ini.');
            }
            $this->pdo->prepare('UPDATE purchase_orders SET status = ? WHERE id = ?')->execute([$target, $id]);
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    /** @param array<int, mixed> $receivedQuantities */
    public function receive(int $id, array $receivedQuantities, int $actorId): void
    {
        $this->pdo->beginTransaction();
        try {
            $order = $this->find($id, true);
            if (!$order || !in_array($order['status'], ['Ordered', 'PartiallyReceived'], true)) {
                throw new \DomainException('Hanya PO Ordered atau Partially Received yang dapat diterima.');
            }
            $hasReceipt = false;
            foreach ($order['details'] as $detail) {
                $requested = (float) ($receivedQuantities[$detail['id']] ?? 0);
                $remaining = (float) $detail['qty'] - (float) $detail['received_qty'];
                if ($requested < 0 || $requested > $remaining) {
                    throw new \DomainException('Jumlah penerimaan ' . $detail['product_name'] . ' tidak valid.');
                }
                if ($requested === 0.0) {
                    continue;
                }
                $hasReceipt = true;
                $this->pdo->prepare('UPDATE purchase_order_details SET received_qty = received_qty + ? WHERE id = ?')->execute([$requested, $detail['id']]);
                $this->addStock($detail['product_id'], $order['warehouse_id'], $requested, $id, $order['po_number'], $actorId);
            }
            if (!$hasReceipt) {
                throw new \DomainException('Isi minimal satu jumlah barang yang diterima.');
            }
            $remaining = $this->pdo->prepare('SELECT COUNT(*) FROM purchase_order_details WHERE purchase_order_id = ? AND received_qty < qty');
            $remaining->execute([$id]);
            $status = (int) $remaining->fetchColumn() === 0 ? 'Received' : 'PartiallyReceived';
            $this->pdo->prepare('UPDATE purchase_orders SET status = ? WHERE id = ?')->execute([$status, $id]);
            $this->pdo->commit();
        } catch (\Throwable $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }

    private function addStock(int $productId, int $warehouseId, float $qty, int $orderId, string $number, int $actorId): void
    {
        $this->pdo->prepare('INSERT INTO stocks(product_id,warehouse_id,stock_in,stock_out,current_stock,updated_at) VALUES(?,?,?,0,?,NOW()) ON DUPLICATE KEY UPDATE stock_in = stock_in + VALUES(stock_in), current_stock = current_stock + VALUES(current_stock), updated_at = NOW()')->execute([$productId, $warehouseId, $qty, $qty]);
        $this->pdo->prepare('INSERT INTO stock_movements(transaction_type,transaction_id,transaction_number,product_id,warehouse_id,qty,created_by,movement_date) VALUES(?,?,?,?,?,?,?,NOW())')->execute([StockMovementType::Receipt->value, $orderId, $number, $productId, $warehouseId, $qty, $actorId]);
    }
}
