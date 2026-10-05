<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\InventoryRepository;
use App\Repository\SalesOrderRepository;
use App\Security\Auth;
use App\Service\SalesOrderService;

final class SalesOrderController extends BaseController
{
    private SalesOrderService $service;

    public function __construct(\PDO $pdo, private SalesOrderRepository $repo, private InventoryRepository $inventory, SalesOrderService $service)
    {
        parent::__construct($pdo);
        $this->service = $service;
    }

    public function index(): void
    {
        Auth::require();
        $role = Auth::role();
        if (!in_array($role, ['Admin', 'Sales', 'WarehouseStaff'], true)) {
            Auth::requireWarehouseAccess();
        }
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'sort' => (string) ($_GET['sort'] ?? 'date_desc'),
        ];
        $result = $this->repo->list($filters, $role === 'Sales' ? Auth::id() : null, (int) ($_GET['current_page'] ?? 1));
        $this->view('sales/index', ['pageTitle' => 'Sales Order', 'result' => $result, 'filters' => $filters]);
    }

    public function create(): void
    {
        Auth::require();
        if (!in_array(Auth::role(), ['Admin', 'Sales'], true)) {
            Auth::requireAdmin();
        }
        $this->view('sales/form', ['pageTitle' => 'Tambah Sales Order', 'products' => $this->inventory->products(), 'customers' => $this->inventory->customers(), 'warehouses' => $this->inventory->warehouses()]);
    }

    public function store(): void
    {
        Auth::require();
        if (!in_array(Auth::role(), ['Admin', 'Sales'], true)) {
            Auth::requireAdmin();
        }
        $this->csrf();
        try {
            $details = $this->details();
            $this->service->create(['customer_id' => (int) ($_POST['customer_id'] ?? 0), 'warehouse_id' => (int) ($_POST['warehouse_id'] ?? 0), 'so_date' => (string) ($_POST['so_date'] ?? date('Y-m-d')), 'notes' => trim((string) ($_POST['notes'] ?? ''))], $details, Auth::id());
            flash('success', 'Sales Order draft berhasil dibuat.');
        } catch (\Throwable $error) {
            flash('error', $this->errorMessage($error, 'Sales Order tidak dapat disimpan. Periksa data dan coba lagi.'));
            http_response_code(422);
            $this->create();
            return;
        }
        redirect('sales');
    }

    public function show(int $id): void
    {
        Auth::require();
        $order = $this->repo->find($id);
        if (!$order) {
            http_response_code(404);
            require_once BASE_PATH . '/views/404.php';
            return;
        }
        if (Auth::role() === 'Sales' && (int) $order['created_by'] !== Auth::id()) {
            http_response_code(403);
            require_once BASE_PATH . '/views/403.php';
            return;
        }
        $this->view('sales/show', ['pageTitle' => 'Detail Sales Order', 'order' => $order]);
    }

    public function status(int $id): void
    {
        Auth::require();
        $this->csrf();
        try {
            $this->service->transition($id, (string) ($_POST['status'] ?? ''), Auth::id(), Auth::role());
            flash('success', 'Status Sales Order diperbarui.');
        } catch (\Throwable $error) {
            flash('error', $this->errorMessage($error, 'Status Sales Order tidak dapat diubah.'));
        }
        redirect('sales');
    }

    private function details(): array
    {
        $details = [];
        foreach ($_POST['product_id'] ?? [] as $index => $productId) {
            $quantityInput = trim((string) (($_POST['qty'] ?? [])[$index] ?? ''));
            $priceInput = trim((string) (($_POST['price'] ?? [])[$index] ?? ''));
            if (trim((string) $productId) === '' && $quantityInput === '' && $priceInput === '') {
                continue;
            }
            if (!ctype_digit((string) $productId) || (int) $productId < 1 || !is_numeric($quantityInput) || (float) $quantityInput <= 0 || !is_numeric($priceInput) || (float) $priceInput < 0) {
                throw new \DomainException('Setiap baris SO harus memiliki produk, jumlah lebih dari nol, dan harga tidak negatif.');
            }
            $details[] = ['product_id' => (int) $productId, 'qty' => (float) $quantityInput, 'price' => (float) $priceInput];
        }
        return $details;
    }
}
