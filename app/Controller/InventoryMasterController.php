<?php

namespace App\Controller;

use App\Repository\InventoryMasterRepository;
use App\Security\Auth;

final class InventoryMasterController extends BaseController
{
    private InventoryMasterRepository $repo;
    public function __construct(\PDO $pdo)
    {
        parent::__construct($pdo);
        $this->repo = new InventoryMasterRepository($pdo);
    }
    public function index(): void
    {
        Auth::requireAdmin();
        $type = (string)($_GET['type'] ?? 'products');
        $c = $this->repo->config($type);
        $f = ['search' => trim((string)($_GET['search'] ?? '')),'status' => (string)($_GET['status'] ?? '')];
        $pagination = $this->repo->paginate($type, $f, (int)($_GET['current_page'] ?? 1));
        $this->view('masters/index', ['pageTitle' => $c['title'],'type' => $type,'config' => $c,'rows' => $pagination['items'],'filters' => $f,'pagination' => $pagination]);
    }
    public function create(): void
    {
        Auth::requireAdmin();
        $this->form((string)($_GET['type'] ?? 'products'), 'create', null);
    }
    public function store(): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $type = (string)($_POST['type'] ?? 'products');
        $this->repo->config($type);
        $d = $this->data($type);
        $d['code'] = $this->repo->nextCode($type);
        $e = $this->validate($type, $d);
        if ($e) {
            $this->form($type, 'create', $d, $e);
            return;
        }try {
            $this->repo->create($type, $d);
            flash('success', 'Data berhasil ditambahkan.');
        }
        catch (\PDOException $x) {
            flash('error', 'Kode data harus unik dan data harus valid.');
        }redirect('masters', ['type' => $type]);
    }
    public function edit(int $id): void
    {
        Auth::requireAdmin();
        $type = (string)($_GET['type'] ?? 'products');
        $row = $this->repo->find($type, $id);
        if (!$row) {
            http_response_code(404);
            require_once BASE_PATH.'/views/404.php';
            return;
        }$this->form($type, 'edit', $row);
    }
    public function update(int $id): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $type = (string)($_POST['type'] ?? 'products');
        $this->repo->config($type);
        $current = $this->repo->find($type, $id);
        if (!$current) {
            http_response_code(404);
            require_once BASE_PATH.'/views/404.php';
            return;
        }$d = $this->data($type);
        $d['code'] = $current['code'];
        $e = $this->validate($type, $d);
        if ($e) {
            $d['id'] = $id;
            $this->form($type, 'edit', $d, $e);
            return;
        }try {
            $this->repo->update($type, $id, $d);
            flash('success', 'Data berhasil diperbarui.');
        }
        catch (\PDOException $x) {
            flash('error', 'Kode data harus unik dan data harus valid.');
        }redirect('masters', ['type' => $type]);
    }
    public function delete(int $id): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $type = (string)($_POST['type'] ?? 'products');
        if ($this->repo->hasReferences($type, $id)) {
            flash('error', 'Data tidak dapat dihapus karena sudah digunakan transaksi/inventory.');
        }
        else {
            try {
                $this->repo->delete($type, $id);
                flash('success', 'Data berhasil dihapus.');
            }
        catch (\PDOException $x) {
                flash('error', 'Data tidak dapat dihapus.');
            }
        }redirect('masters', ['type' => $type]);
    }
    private function form(string $type, string $mode, ?array $row, array $errors = []): void
    {
        $c = $this->repo->config($type);
        $defaults = ['code' => $this->repo->nextCode($type),'name' => '','category' => '','unit' => 'pcs','purchase_price' => '0','selling_price' => '0','minimum_stock' => '0','address' => '','phone' => '','email' => '','status' => 'Active'];
        $this->view('masters/form', ['pageTitle' => ($mode === 'create' ? 'Tambah ' : 'Edit ').$c['singular'],'type' => $type,'config' => $c,'mode' => $mode,'row' => $row ?: $defaults,'errors' => $errors]);
    }
    private function data(string $type): array
    {
        $c = $this->repo->config($type);
        $d = [];
        foreach ($c['fields'] as $f) {
            $v = $_POST[$f] ?? '';
            $d[$f] = in_array($f, ['purchase_price','selling_price','minimum_stock'], true) ? (float)$v : trim((string)$v);
        }return $d;
    }
    private function validate(string $type, array $d): array
    {
        $e = [];
        if (($d['code'] ?? '') === '') {
            $e['code'] = 'Kode wajib diisi.';
        }
        if (($d['name'] ?? '') === '') {
            $e['name'] = 'Nama wajib diisi.';
        }
        if ($type === 'products') {
            if (($d['unit'] ?? '') === '') {
                $e['unit'] = 'Satuan wajib diisi.';
            }
        if ($d['purchase_price'] < 0 || $d['selling_price'] < 0 || $d['minimum_stock'] < 0) {
                $e['price'] = 'Nilai tidak boleh negatif.';
            }
        }return $e;
    }
}
