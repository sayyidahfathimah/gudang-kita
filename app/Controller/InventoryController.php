<?php

namespace App\Controller;

use App\Repository\InventoryRepository;
use App\Security\Auth;

final class InventoryController extends BaseController
{
    private InventoryRepository $repo;
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new InventoryRepository($pdo);
    }
    public function stock(): void
    {
        Auth::requireWarehouseAccess();
        $stockStatus = (string)($_GET['stock_status'] ?? 'available');
        if (!in_array($stockStatus, ['available', 'zero', 'all'], true)) {
            $stockStatus = 'available';
        }
        $f = ['search' => trim((string)($_GET['search'] ?? '')),'warehouse_id' => (string)($_GET['warehouse_id'] ?? ''),'stock_status' => $stockStatus];
        $result = paginate($this->repo->stockRows($f), (int)($_GET['current_page'] ?? 1));
        $this->view('inventory/stock', ['pageTitle' => 'Stok','result' => $result,'warehouses' => $this->repo->warehouses(),'filters' => $f]);
    }
    public function product(int $id): void
    {
        Auth::requireWarehouseAccess();
        $product = $this->repo->productStockBreakdown($id);
        if (!$product) {
            http_response_code(404);
            require_once BASE_PATH.'/views/404.php';
            return;
        }
        $this->view('inventory/product', ['pageTitle' => 'Stok Produk', 'product' => $product]);
    }
    public function movements(): void
    {
        Auth::requireWarehouseAccess();
        $f = ['product_id' => (string)($_GET['product_id'] ?? ''),'warehouse_id' => (string)($_GET['warehouse_id'] ?? '')];
        $result = paginate($this->repo->movements($f), (int)($_GET['current_page'] ?? 1));
        $this->view('inventory/movements', ['pageTitle' => 'Stock Movement','result' => $result,'products' => $this->repo->products(),'warehouses' => $this->repo->warehouses(),'filters' => $f]);
    }
}
