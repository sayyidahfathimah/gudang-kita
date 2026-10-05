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
        $type = (string)($_GET['type'] ?? 'products');
        if (Auth::role() !== 'Admin') {
            if ($type !== 'products' || !in_array(Auth::role(), ['Sales','WarehouseStaff'], true)) {
                http_response_code(403);
                require_once BASE_PATH.'/views/403.php';
                return;
            }
            $this->renderCatalog();
            return;
        }
        $c = $this->repo->config($type);
        $f = $this->filters();
        $pagination = $this->repo->paginate($type, $f, (int)($_GET['current_page'] ?? 1));
        $this->view('masters/index', ['pageTitle' => $c['title'],'type' => $type,'config' => $c,'rows' => $pagination['items'],'filters' => $f,'pagination' => $pagination,'categories' => $type === 'products' ? $this->repo->categories() : []]);
    }
    public function create(): void
    {
        Auth::requireAdmin();
        $this->form((string)($_GET['type'] ?? 'products'), 'create', null);
    }

    public function catalog(): void
    {
        if (!in_array(Auth::role(), ['Sales', 'WarehouseStaff'], true)) {
            http_response_code(403);
            require_once BASE_PATH.'/views/403.php';
            return;
        }
        $this->renderCatalog();
    }
    private function filters(): array
    {
        $filters = [];
        foreach (['search', 'status', 'category_id', 'stock_status', 'sort'] as $key) {
            $filters[$key] = trim((string) ($_GET[$key] ?? ''));
        }
        return $filters;
    }

    private function renderCatalog(): void
    {
        $filters = $this->filters();
        $filters['status'] = 'Active';
        $pagination = $this->repo->paginate('products', $filters, (int) ($_GET['current_page'] ?? 1));
        $this->view('masters/catalog', [
            'pageTitle' => 'Katalog Produk', 'products' => $pagination['items'],
            'pagination' => $pagination, 'filters' => $filters, 'categories' => $this->repo->categories(),
        ]);
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
        }
        $imagePath = null;
        try {
            if ($type === 'products') {
                $imagePath = $this->uploadProductImage();
                if ($imagePath !== null) {
                    $d['image_path'] = $imagePath;
                }
            }
            $this->repo->create($type, $d);
            flash('success', 'Data berhasil ditambahkan.');
        }
        catch (\PDOException $x) {
            $this->removeProductImage($imagePath);
            flash('error', 'Kode data harus unik dan data harus valid.');
        } catch (\InvalidArgumentException $x) {
            $this->form($type, 'create', $d, ['image' => $x->getMessage()]);
            return;
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
        }
        $newImage = null;
        try {
            if ($type === 'products') {
                $newImage = $this->uploadProductImage();
                $d['image_path'] = $newImage ?? ($current['image_path'] ?? null);
            }
            $this->repo->update($type, $id, $d);
            if ($newImage !== null) {
                $this->removeProductImage($current['image_path'] ?? null);
            }
            flash('success', 'Data berhasil diperbarui.');
        }
        catch (\PDOException $x) {
            $this->removeProductImage($newImage);
            flash('error', 'Kode data harus unik dan data harus valid.');
        } catch (\InvalidArgumentException $x) {
            $d['id'] = $id;
            $d['image_path'] = $current['image_path'] ?? null;
            $this->form($type, 'edit', $d, ['image' => $x->getMessage()]);
            return;
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
                $imagePath = $type === 'products' ? ($this->repo->find($type, $id)['image_path'] ?? null) : null;
                $this->repo->delete($type, $id);
                $this->removeProductImage($imagePath);
                flash('success', 'Data berhasil dihapus.');
            }
        catch (\PDOException $x) {
                flash('error', 'Data tidak dapat dihapus.');
            }
        }redirect('masters', ['type' => $type]);
    }
    public function deactivate(int $id): void
    {
        Auth::requireAdmin();
        $this->csrf();
        $type = (string) ($_POST['type'] ?? 'products');
        if (!in_array($type, ['products', 'categories', 'suppliers', 'customers', 'warehouses'], true)) {
            flash('error', 'Jenis data tidak valid.');
        } else {
            try {
                $this->repo->deactivate($type, $id);
                flash('success', 'Data berhasil dinonaktifkan.');
            } catch (\PDOException) {
                flash('error', 'Data tidak dapat dinonaktifkan.');
            }
        }
        redirect('masters', ['type' => $type]);
    }
    private function form(string $type, string $mode, ?array $row, array $errors = []): void
    {
        $c = $this->repo->config($type);
        $defaults = ['code' => $this->repo->nextCode($type),'name' => '','description' => '','category_id' => '','unit' => 'pcs','purchase_price' => '0','selling_price' => '0','minimum_stock' => '0','address' => '','phone' => '','email' => '','status' => 'Active'];
        if ($type === 'products' && $row !== null && empty($row['image_path'])) {
            $row['image_path'] = null;
        }
        $this->view('masters/form', ['pageTitle' => ($mode === 'create' ? 'Tambah ' : 'Edit ').$c['singular'],'type' => $type,'config' => $c,'mode' => $mode,'row' => $row ?: $defaults,'errors' => $errors, 'categories' => $type === 'products' ? $this->repo->categories() : []]);
    }
    private function data(string $type): array
    {
        $c = $this->repo->config($type);
        $d = [];
        foreach ($c['fields'] as $f) {
            $v = $_POST[$f] ?? '';
            if (in_array($f, ['purchase_price', 'selling_price', 'minimum_stock'], true)) {
                $d[$f] = (float) $v;
            } elseif ($f === 'category_id') {
                $d[$f] = (int) $v;
            } else {
                $d[$f] = trim((string) $v);
            }
        }
        return $d;
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
        if ($type === 'categories' && ($d['description'] ?? '') === '') {
            $e['description'] = 'Deskripsi wajib diisi.';
        }
        if ($type === 'products') {
            if ((int) ($d['category_id'] ?? 0) <= 0) {
                $e['category_id'] = 'Kategori wajib dipilih.';
            }
            if (($d['unit'] ?? '') === '') {
                $e['unit'] = 'Satuan wajib diisi.';
            }
        if ($d['purchase_price'] < 0 || $d['selling_price'] < 0 || $d['minimum_stock'] < 0) {
                $e['price'] = 'Nilai tidak boleh negatif.';
            }
        }return $e;
    }

    private function uploadProductImage(): ?string
    {
        $file = $_FILES['image'] ?? null;
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new \InvalidArgumentException('Gambar tidak berhasil diunggah. Silakan pilih file kembali.');
        }
        if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) {
            throw new \InvalidArgumentException('Ukuran gambar maksimal 2 MB.');
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!isset($extensions[$mime])) {
            throw new \InvalidArgumentException('Format gambar yang diterima hanya JPG, PNG, atau WebP.');
        }
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false || $imageInfo[0] < 1 || $imageInfo[1] < 1 || $imageInfo[0] > 6000 || $imageInfo[1] > 6000) {
            throw new \InvalidArgumentException('Gambar tidak valid atau dimensinya melebihi 6000 × 6000 piksel.');
        }
        $directory = BASE_PATH.'/public/uploads/products';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \InvalidArgumentException('Folder penyimpanan gambar tidak tersedia.');
        }
        $filename = bin2hex(random_bytes(16)).'.'.$extensions[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory.'/'.$filename)) {
            throw new \InvalidArgumentException('Gambar tidak dapat disimpan.');
        }
        return 'uploads/products/'.$filename;
    }

    private function removeProductImage(?string $path): void
    {
        if ($path !== null && preg_match('#^uploads/products/[a-f0-9]{32}\.(jpg|png|webp)$#', $path)) {
            $absolutePath = BASE_PATH.'/public/'.$path;
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
        }
    }
}
