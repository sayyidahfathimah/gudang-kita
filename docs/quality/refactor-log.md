# Refactoring Log

Pembaruan 5 Oktober 2026. Cuplikan di bawah diambil dari perubahan source dalam pengerjaan brief Intermediate. Hasilnya diperiksa dengan PHPStan level 5, unit test, dan integration test MySQL.

## 1. Long Method / tanggung jawab bercampur — Extract Repository

Sebelum: `ReportController::downloadCsv()` membuat SQL, menjalankan query, memilih kolom CSV, dan mengirim response HTTP.

```php
$statement = $this->pdo->prepare('SELECT ... FROM stock_movements ... WHERE m.movement_date >= ?');
$statement->execute([$filters['from'], $filters['to']]);
$rows = $statement->fetchAll();
```

Sesudah: query pindah ke `ReportRepository`, sedangkan controller mengatur request, hak akses, dan response.

```php
$rows = $this->reports->movementRows($filters['from'], $filters['to']);
```

## 2. Authorization bercampur dengan query — Extract Policy

Sebelum: satu panggilan `Auth::requireAdmin()` dalam `ReportController` menutup seluruh laporan bagi dua role operasional.

```php
Auth::requireAdmin();
```

Sesudah: `ReportAccessPolicy` mendefinisikan laporan yang boleh dibaca tiap role; controller memeriksa kebijakan sebelum mengambil data. Repository membatasi order Sales berdasarkan `created_by`.

```php
if (!$this->access->canDownload($role, $report)) {
    $this->forbidden();
}
$rows = $this->reports->orderRows($from, $to, Auth::id());
```

## 3. Magic String — Replace with Enum

Sebelum: repository PO dan SO menanam literal `'Receipt'` serta `'Issue'` pada SQL insert ledger.

```php
$pdo->prepare("INSERT INTO stock_movements (...) VALUES('Issue', ...)")->execute($params);
```

Sesudah: `StockMovementType` menyatakan tiga tipe domain yang sesuai constraint MySQL.

```php
$pdo->prepare('INSERT INTO stock_movements (...) VALUES(?, ...)')->execute([
    StockMovementType::Issue->value, /* referensi dan qty */
]);
```

## Audit SRP

`ReportController` sebelumnya memegang HTTP, otorisasi, query, dan penyusunan CSV. Setelah pemisahan, `ReportAccessPolicy` memegang aturan role, `ReportRepository` memegang query, dan controller menyusun response. Batas yang masih tersisa: controller masih membuat repository default saat dependency tidak diinjeksi; [technical debt](tech-debt.md) mencatat pekerjaan lanjutan.

## 4. Tautan pagination bercampur dengan navigasi — Extract Function

Sebelum: `paginationLinks()` membentuk query dan HTML tiap tautan lewat closure di dalam metode navigasi.

```php
$link = function (int $page, string $label) use ($params) {
    $q = $params;
    $q['current_page'] = $page;
    return '<a href="?'.e(http_build_query($q)).'">'.$label.'</a>';
};
```

Sesudah: `paginationLink()` hanya membentuk satu tautan, sedangkan `paginationLinks()` menentukan urutan tombol sebelumnya, nomor halaman, dan berikutnya. Unit test membuktikan filter dan escaping tetap sama.

```php
$html .= paginationLink($result['page'] - 1, '‹ Sebelumnya', $params);
```

Refactor yang memperbaiki kode lama ini tercatat terpisah pada commit `e60898f` (`refactor: extract pagination link rendering`). Perubahan lain di working tree belum termasuk commit tersebut.
