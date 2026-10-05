<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PurchaseOrderRepository;
use App\Repository\InventoryRepository;
use App\Security\Auth;
use App\Contract\PurchaseOrderWorkflowRepositoryInterface;
use App\Service\PurchaseOrderService;

final class PurchaseOrderController extends BaseController
{
    private PurchaseOrderRepository $repo;
    private InventoryRepository $inv;
    private PurchaseOrderService $service;

    public function __construct(\PDO $pdo, ?PurchaseOrderRepository $repo = null, ?InventoryRepository $inventory = null, ?PurchaseOrderService $service = null)
    {
        parent::__construct($pdo);
        $this->repo = $repo ?? new PurchaseOrderRepository($pdo);
        $this->inv = $inventory ?? new InventoryRepository($pdo);
        $this->service = $service ?? new PurchaseOrderService($this->repo);
    }

    public function index(): void
    {
        Auth::requireWarehouseAccess();
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'sort' => (string) ($_GET['sort'] ?? 'date_desc'),
        ];
        $this->view('purchase/index', [
            'pageTitle' => 'Purchase Order',
            'result' => $this->repo->list($filters, (int) ($_GET['current_page'] ?? 1)),
            'filters' => $filters,
        ]);
    }

    public function create(): void
    {
        Auth::requireWarehouseAccess();

        $this->view('purchase/form', [
            'pageTitle' => 'Tambah Purchase Order',
            'products' => $this->inv->products(),
            'suppliers' => $this->inv->suppliers(),
            'warehouses' => $this->inv->warehouses(),
        ]);
    }

    public function store(): void
    {
        Auth::requireWarehouseAccess();
        $this->csrf();

        try {
            $details = $this->details();
            $this->service->create([
                'supplier_id' => (int) ($_POST['supplier_id'] ?? 0),
                'warehouse_id' => (int) ($_POST['warehouse_id'] ?? 0),
                'po_date' => (string) ($_POST['po_date'] ?? date('Y-m-d')),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
            ], $details);

            flash('success', 'Purchase Order berhasil dibuat.');
        }
        catch (\Throwable $e) {
            flash('error', $this->errorMessage($e, 'Purchase Order tidak dapat disimpan. Periksa data dan coba lagi.'));
            http_response_code(422);
            $this->create();
            return;
        }

        redirect('purchase');
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
            $this->service->transition($id, $status, Auth::role());
            flash('success', 'Status Purchase Order diperbarui.');
        }
        catch (\Throwable $e) {
            flash('error', $this->errorMessage($e, 'Status Purchase Order tidak dapat diubah.'));
        }

        redirect('purchase');
    }


    public function receive(int $id): void
    {
        Auth::requireWarehouseAccess();
        $this->csrf();
        try {
            $this->service->receive($id, $_POST['received_qty'] ?? [], Auth::id());
            flash('success', 'Penerimaan barang dan stok berhasil dicatat.');
        } catch (\Throwable $e) {
            flash('error', $this->errorMessage($e, 'Penerimaan barang tidak dapat diproses.'));
        }
        redirect('purchase', ['action' => 'view', 'id' => $id]);
    }

    private function details(): array
    {
        $out = [];

        $ids = $_POST['product_id'] ?? [];
        $qty = $_POST['qty'] ?? [];
        $price = $_POST['price'] ?? [];

        foreach ($ids as $i => $id) {
            $quantityInput = trim((string) ($qty[$i] ?? ''));
            $priceInput = trim((string) ($price[$i] ?? ''));
            if (trim((string) $id) === '' && $quantityInput === '' && $priceInput === '') {
                continue;
            }
            if (!ctype_digit((string) $id) || (int) $id < 1 || !is_numeric($quantityInput) || (float) $quantityInput <= 0 || !is_numeric($priceInput) || (float) $priceInput < 0) {
                throw new \DomainException('Setiap baris PO harus memiliki produk, jumlah lebih dari nol, dan harga tidak negatif.');
            }
            $out[] = ['product_id' => (int) $id,'qty' => (float) $quantityInput,'price' => (float) $priceInput];
        }

        return $out;
    }
}
