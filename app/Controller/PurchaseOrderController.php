<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repository\PurchaseOrderRepository;
use App\Repository\InventoryRepository;
use App\Security\Auth;

final class PurchaseOrderController extends BaseController
{
    private PurchaseOrderRepository $repo;
    private InventoryRepository $inv;

    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new PurchaseOrderRepository($pdo);
        $this->inv = new InventoryRepository($pdo);
    }

    public function index(): void
    {
        Auth::requireWarehouseAccess();

        $this->view('purchase/index', [
            'pageTitle' => 'Purchase Order',
            'result' => paginate($this->repo->all(), (int) ($_GET['current_page'] ?? 1)),
        ]);
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->view('purchase/form', [
            'pageTitle' => 'Tambah Purchase Order',
            'products' => $this->inv->products(),
            'suppliers' => $this->inv->suppliers(),
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
            redirect('purchase');
            return;
        }

        try {
            $this->repo->create([
                'supplier_id' => (int) ($_POST['supplier_id'] ?? 0),
                'warehouse_id' => (int) ($_POST['warehouse_id'] ?? 0),
                'po_date' => (string) ($_POST['po_date'] ?? date('Y-m-d')),
                'status' => (string) ($_POST['status'] ?? 'Draft'),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
            ], $details);

            flash('success', 'Purchase Order berhasil dibuat.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('purchase');
    }

    public function show(int $id): void
    {
        Auth::requireWarehouseAccess();

        $order = $this->repo->find($id);

        if (!$order) {
            http_response_code(404);
            require BASE_PATH . '/views/404.php';
            return;
        }

        $this->view('purchase/show', [
            'pageTitle' => 'Detail Purchase Order',
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
            flash('success', 'Status Purchase Order diperbarui.');
        } catch (\Throwable $e) {
            flash('error', $e->getMessage());
        }

        redirect('purchase');
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
