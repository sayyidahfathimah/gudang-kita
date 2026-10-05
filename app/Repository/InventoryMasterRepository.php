<?php

declare(strict_types=1);

namespace App\Repository;

final class InventoryMasterRepository
{
    private const BY_ID = ' WHERE id = ?';
    private array $config = [
        'categories' => ['table' => 'categories', 'title' => 'Kategori', 'singular' => 'Kategori', 'fields' => ['code', 'name', 'description', 'status'], 'search' => ['code', 'name']],
        'products' => ['table' => 'products', 'title' => 'Produk', 'singular' => 'Produk', 'fields' => ['code', 'name', 'category_id', 'unit', 'purchase_price', 'selling_price', 'minimum_stock', 'status'], 'search' => ['code', 'name']],
        'customers' => ['table' => 'customers', 'title' => 'Customer', 'singular' => 'Customer', 'fields' => ['code', 'name', 'address', 'phone', 'email', 'status'], 'search' => ['code', 'name', 'email']],
        'suppliers' => ['table' => 'suppliers', 'title' => 'Supplier', 'singular' => 'Supplier', 'fields' => ['code', 'name', 'address', 'phone', 'email', 'status'], 'search' => ['code', 'name', 'email']],
        'warehouses' => ['table' => 'warehouses', 'title' => 'Gudang', 'singular' => 'Gudang', 'fields' => ['code', 'name', 'address', 'phone', 'status'], 'search' => ['code', 'name']],
    ];

    public function __construct(private \PDO $pdo)
    {
    }

    public function config(string $type): array
    {
        return $this->config[$type] ?? throw new \InvalidArgumentException('Master tidak valid.');
    }

    public function categories(): array
    {
        return $this->pdo->query("SELECT id, code, name FROM categories WHERE status = 'Active' ORDER BY name")->fetchAll();
    }

    public function catalog(): array
    {
        return $this->pdo->query("SELECT p.id,p.code,p.name,p.unit,p.purchase_price,p.selling_price,p.minimum_stock,c.name AS category_name FROM products p JOIN categories c ON c.id=p.category_id WHERE p.status='Active' ORDER BY p.name")->fetchAll();
    }

    public function paginate(string $type, array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $config = $this->config($type);
        [$from, $select] = $this->source($type);
        $where = [];
        $params = [];
        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $columns = array_map(static fn (string $column): string => 'm.' . $column, $config['search']);
            if ($type === 'products') {
                $columns[] = 'c.name';
            }
            $where[] = '(' . implode(' LIKE ? OR ', $columns) . ' LIKE ?)';
            $params = array_merge($params, array_fill(0, count($columns), '%' . $search . '%'));
        }
        $status = (string) ($filters['status'] ?? '');
        if ($status !== '') {
            $where[] = 'm.status = ?';
            $params[] = $status;
        }
        if ($type === 'products' && ($filters['category_id'] ?? '') !== '') {
            $where[] = 'm.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if ($type === 'products' && in_array(($filters['stock_status'] ?? ''), ['low','normal'], true)) {
            $comparison = $filters['stock_status'] === 'low' ? '<=' : '>';
            $where[] = "COALESCE((SELECT SUM(sx.current_stock) FROM stocks sx WHERE sx.product_id=m.id),0) {$comparison} m.minimum_stock";
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $count = $this->pdo->prepare('SELECT COUNT(*) ' . $from . $whereSql);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $orders = ['name_asc' => 'm.name ASC, m.id ASC', 'name_desc' => 'm.name DESC, m.id DESC',
            'code_asc' => 'm.code ASC, m.id ASC', 'code_desc' => 'm.code DESC, m.id DESC'];
        $order = $orders[$filters['sort'] ?? ''] ?? 'm.id DESC';
        $statement = $this->pdo->prepare('SELECT ' . $select . ' ' . $from . $whereSql . ' ORDER BY ' . $order . ' LIMIT ' . $perPage . ' OFFSET ' . $offset);
        $statement->execute($params);
        return ['items' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage];
    }

    public function find(string $type, int $id): ?array
    {
        [$from, $select] = $this->source($type);
        $statement = $this->pdo->prepare('SELECT ' . $select . ' ' . $from . ' WHERE m.id = ?');
        $statement->execute([$id]);
        return $statement->fetch() ?: null;
    }

    public function nextCode(string $type): string
    {
        $config = $this->config($type);
        $prefix = ['categories' => 'CAT', 'products' => 'PRD', 'customers' => 'CUS', 'suppliers' => 'SUP', 'warehouses' => 'WH'][$type];
        $start = strlen($prefix) + 1;
        $statement = $this->pdo->prepare('SELECT MAX(CAST(SUBSTRING(code, ?) AS UNSIGNED)) FROM ' . $config['table'] . ' WHERE code REGEXP ?');
        $statement->execute([$start, '^' . $prefix . '[0-9]+$']);
        return $prefix . str_pad((string) ((int) $statement->fetchColumn() + 1), 3, '0', STR_PAD_LEFT);
    }

    public function create(string $type, array $data): int
    {
        $config = $this->config($type);
        $fields = $config['fields'];
        if ($type === 'products' && array_key_exists('image_path', $data)) {
            $fields[] = 'image_path';
        }
        $statement = $this->pdo->prepare('INSERT INTO ' . $config['table'] . '(' . implode(',', $fields) . ') VALUES(' . implode(',', array_fill(0, count($fields), '?')) . ')');
        $statement->execute(array_map(static fn (string $field): mixed => $data[$field] ?? null, $fields));
        return (int) $this->pdo->lastInsertId();
    }

    public function update(string $type, int $id, array $data): void
    {
        $config = $this->config($type);
        $fields = $config['fields'];
        if ($type === 'products' && array_key_exists('image_path', $data)) {
            $fields[] = 'image_path';
        }
        $statement = $this->pdo->prepare('UPDATE ' . $config['table'] . ' SET ' . implode(',', array_map(static fn (string $field): string => $field . ' = ?', $fields)) . self::BY_ID);
        $params = array_map(static fn (string $field): mixed => $data[$field] ?? null, $fields);
        $params[] = $id;
        $statement->execute($params);
    }

    public function delete(string $type, int $id): void
    {
        $config = $this->config($type);
        $statement = $this->pdo->prepare('DELETE FROM ' . $config['table'] . self::BY_ID);
        $statement->execute([$id]);
    }

    public function deactivate(string $type, int $id): void
    {
        $config = $this->config($type);
        $statement = $this->pdo->prepare('UPDATE ' . $config['table'] . " SET status = 'Inactive' WHERE id = ?");
        $statement->execute([$id]);
    }

    public function hasReferences(string $type, int $id): bool
    {
        $map = ['categories' => [['products', 'category_id']], 'products' => [['purchase_order_details', 'product_id'], ['sales_order_details', 'product_id'], ['stocks', 'product_id'], ['stock_movements', 'product_id']], 'suppliers' => [['purchase_orders', 'supplier_id']], 'customers' => [['sales_orders', 'customer_id']], 'warehouses' => [['purchase_orders', 'warehouse_id'], ['sales_orders', 'warehouse_id'], ['stocks', 'warehouse_id'], ['stock_movements', 'warehouse_id']]];
        foreach ($map[$type] ?? [] as [$table, $column]) {
            $statement = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
            $statement->execute([$id]);
            if ((int) $statement->fetchColumn() > 0) {
                return true;
            }
        }
        return false;
    }

    private function source(string $type): array
    {
        $table = $this->config($type)['table'];
        return $type === 'products'
            ? ['FROM products m LEFT JOIN categories c ON c.id = m.category_id', 'm.*, c.name AS category_name']
            : ['FROM ' . $table . ' m', 'm.*'];
    }
}
