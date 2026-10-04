<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SalesOrderRepository;
use App\Repository\InventoryRepository;
use App\Security\Auth;

final class SalesOrderController extends BaseController
{
    private SalesOrderRepository $repo;
    private InventoryRepository $inv;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new SalesOrderRepository($pdo);
        $this->inv = new InventoryRepository($pdo);
    }

    public function index(): void
    {
        Auth::requireWarehouseAccess();

        $this->view('sales/index', [
            'pageTitle' => 'Sales Order',
            'result' => paginate($this->repo->all(), (int) ($_GET['current_page'] ?? 1)),
        ]);
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->view('sales/form', [
            'pageTitle' => 'Tambah Sales Order',
            'products' => $this->inv->products(),
            'customers' => $this->inv->customers(),
            'warehouses' => $this->inv->warehouses(),
        ]);
    }

    public function store(): void
    {
        Auth::requireAdmin();
        $this->csrf();

        $details = $this->details();

        if (!$details) {
            flash('error', 'Minimal satu detail produk harus diisi.');
            redirect('sales');
            return;
        }

        try {
            $this->repo->create([
                'customer_id' => (int) ($_POST['customer_id'] ?? 0),
                'warehouse_id' => (int) ($_POST['warehouse_id'] ?? 0),
                'so_date' => (string) ($_POST['so_date'] ?? date('Y-m-d')),
                'status' => (string) ($_POST['status'] ?? 'Draft'),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
            ], $details);

            flash('success', 'Sales Order berhasil dibuat.');
        }
        catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('sales');
    }

    public function show(int $id): void
    {
        Auth::requireWarehouseAccess();

        $order = $this->repo->find($id);

        if (!$order) {
            http_response_code(404);
            require_once BASE_PATH . '/views/404.php';
            return;
        }

        $this->view('sales/show', [
            'pageTitle' => 'Detail Sales Order',
            'order' => $order,
        ]);
    }

    public function status(int $id): void
    {
        Auth::requireWarehouseAccess();
        $this->csrf();

        try {
            $status = (string) ($_POST['status'] ?? '');
            $this->repo->setStatus($id, $status);
            flash('success', 'Status Sales Order diperbarui.');
        }
        catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('sales');
    }

    private function details(): array
    {
        $out = [];

        $ids = $_POST['product_id'] ?? [];
        $qty = $_POST['qty'] ?? [];
        $price = $_POST['price'] ?? [];

        foreach ($ids as $i => $id) {
            $productId = (int) $id;
            $quantity = (float) ($qty[$i] ?? 0);
            $unitPrice = (float) ($price[$i] ?? 0);

            if ($productId > 0 && $quantity > 0) {
                $out[] = [
                    'product_id' => $productId,
                    'qty' => $quantity,
                    'price' => $unitPrice,
                ];
            }
        }

        return $out;
    }
}
