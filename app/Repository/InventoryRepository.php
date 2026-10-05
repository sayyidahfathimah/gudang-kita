<?php

namespace App\Repository;

final class InventoryRepository
{
    public function __construct(private \PDO $pdo)
    {
    }
    public function products(): array
    {
        return $this->pdo->query("SELECT id,code,name,unit,purchase_price,selling_price FROM products WHERE status='Active' ORDER BY name")->fetchAll();
    }
    public function suppliers(): array
    {
        return $this->pdo->query("SELECT id,code,name FROM suppliers WHERE status='Active' ORDER BY name")->fetchAll();
    }
    public function customers(): array
    {
        return $this->pdo->query("SELECT id,code,name FROM customers WHERE status='Active' ORDER BY name")->fetchAll();
    }
    public function warehouses(): array
    {
        return $this->pdo->query("SELECT id,code,name FROM warehouses WHERE status='Active' ORDER BY name")->fetchAll();
    }
    public function stockRows(array $f = []): array
    {
        $where = [];
        $p = [];
        $s = trim((string)($f['search'] ?? ''));
        if ($s !== '') {
            $where[] = '(p.code LIKE ? OR p.name LIKE ? OR w.name LIKE ?)';
            $p[] = "%$s%";
            $p[] = "%$s%";
            $p[] = "%$s%";
        }
        if (($f['warehouse_id'] ?? '') !== '') {
            $where[] = 's.warehouse_id=?';
            $p[] = (int)$f['warehouse_id'];
        }
        if (($f['stock_status'] ?? 'all') === 'available') {
            $where[] = 's.current_stock > 0';
        } elseif (($f['stock_status'] ?? '') === 'zero') {
            $where[] = 's.current_stock = 0';
        }
        $sql = 'SELECT s.*,p.code product_code,p.name product_name,p.unit,p.minimum_stock,w.code warehouse_code,w.name warehouse_name FROM stocks s JOIN products p ON p.id=s.product_id JOIN warehouses w ON w.id=s.warehouse_id'.($where ? ' WHERE '.implode(' AND ', $where) : '').' ORDER BY p.name,w.name';
        $q = $this->pdo->prepare($sql);
        $q->execute($p);
        return $q->fetchAll();
    }
    public function movements(array $f = []): array
    {
        $where = [];
        $p = [];
        if (($f['product_id'] ?? '') !== '') {
            $where[] = 'm.product_id=?';
            $p[] = (int)$f['product_id'];
        }
        if (($f['warehouse_id'] ?? '') !== '') {
            $where[] = 'm.warehouse_id=?';
            $p[] = (int)$f['warehouse_id'];
        }$sql = 'SELECT m.*,p.code product_code,p.name product_name,w.name warehouse_name,u.name actor_name FROM stock_movements m JOIN products p ON p.id=m.product_id JOIN warehouses w ON w.id=m.warehouse_id LEFT JOIN users u ON u.id=m.created_by'.($where ? ' WHERE '.implode(' AND ', $where) : '').' ORDER BY m.movement_date DESC,m.id DESC LIMIT 500';
        $q = $this->pdo->prepare($sql);
        $q->execute($p);
        return $q->fetchAll();
    }
    public function lowStock(): array
    {
        return $this->pdo->query("SELECT s.*,p.code product_code,p.name product_name,p.minimum_stock,p.unit,w.name warehouse_name FROM stocks s JOIN products p ON p.id=s.product_id JOIN warehouses w ON w.id=s.warehouse_id WHERE s.current_stock <= p.minimum_stock ORDER BY s.current_stock ASC,p.name LIMIT 10")->fetchAll();
    }
    public function summary(): array
    {
        return ['products' => (int)$this->pdo->query("SELECT COUNT(*) FROM products WHERE status='Active'")->fetchColumn(),'stock_in' => (float)$this->pdo->query('SELECT COALESCE(SUM(stock_in),0) FROM stocks')->fetchColumn(),'stock_out' => (float)$this->pdo->query('SELECT COALESCE(SUM(stock_out),0) FROM stocks')->fetchColumn(),'current_stock' => (float)$this->pdo->query('SELECT COALESCE(SUM(current_stock),0) FROM stocks')->fetchColumn(),'inventory_value' => (float)$this->pdo->query('SELECT COALESCE(SUM(s.current_stock*p.purchase_price),0) FROM stocks s JOIN products p ON p.id=s.product_id')->fetchColumn(),'low_stock' => (int)$this->pdo->query('SELECT COUNT(*) FROM stocks s JOIN products p ON p.id=s.product_id WHERE s.current_stock <= p.minimum_stock')->fetchColumn()];
    }

    public function productStockBreakdown(int $productId): ?array
    {
        $product = $this->pdo->prepare('SELECT id,code,name,unit,minimum_stock,status FROM products WHERE id=?');
        $product->execute([$productId]);
        $row = $product->fetch();
        if (!$row) {
            return null;
        }
        $stocks = $this->pdo->prepare('SELECT w.id warehouse_id,w.code warehouse_code,w.name warehouse_name,COALESCE(s.stock_in,0) stock_in,COALESCE(s.stock_out,0) stock_out,COALESCE(s.current_stock,0) current_stock,COALESCE(s.updated_at,NULL) updated_at FROM warehouses w LEFT JOIN stocks s ON s.warehouse_id=w.id AND s.product_id=? ORDER BY w.name');
        $stocks->execute([$productId]);
        $row['warehouses'] = $stocks->fetchAll();
        $row['total_stock'] = array_sum(array_map(static fn (array $stock): float => (float) $stock['current_stock'], $row['warehouses']));
        return $row;
    }
}
