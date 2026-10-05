# Gudang Kita — Inventory Management System

## Tujuan

Gudang Kita mengelola master persediaan, pembelian, penjualan, stok per gudang, dan riwayat pergerakan stok. Aplikasi mencegah stok minus saat barang dikeluarkan serta memisahkan tugas Sales, Admin, dan Petugas Gudang.

## Peran dan alur kerja

| Peran | Tanggung jawab |
| --- | --- |
| **Sales** | Membuat dan mengajukan Sales Order miliknya; mengunduh laporan order miliknya. |
| **Admin** | Mengelola master data dan pengguna, menyetujui atau menolak SO yang diajukan, serta melihat seluruh laporan. |
| **Petugas Gudang** | Membuat PO, menerima barang, mengeluarkan barang dari SO yang disetujui, serta mengunduh laporan stok dan ledger. |

```text
PO Draft → Ordered → PartiallyReceived / Received → stok bertambah + Receipt
SO Draft → PendingApproval → Approved → Fulfilled → stok berkurang + Issue
```

Penerimaan PO dapat dilakukan sebagian. Setiap penerimaan menambah `received_qty`, stok, dan satu riwayat `Receipt`. Saat seluruh detail telah diterima, status PO menjadi `Received`.

Saat Petugas Gudang memenuhi SO, aplikasi mengunci baris stok dengan transaksi database, memastikan stok cukup, mengurangi stok, membuat movement `Issue`, lalu mengubah status menjadi `Fulfilled` dalam satu transaksi.

Database latihan aktif yang berasal dari versi lama memiliki histori movement yang tidak lengkap. Selisih saldo awal dicatat sebagai movement `Adjustment` bernomor `LEGACY-BASE-*`, dengan arah masuk/keluar dan catatan asalnya. Saldo stok tidak diubah oleh rekonsiliasi; aktor dan detail transaksi historis yang tidak tersedia tetap tidak diisi. [Alur data dan ERD](docs/dokumentasi-summary.md) menjelaskan batas ini.

## Modul

- **Autentikasi**: login menggunakan email, password hash, session aman, dan CSRF.
- **Master data**: kategori, produk, customer, supplier, gudang, dan pengguna.
- **Purchase Order**: pembuatan, pemesanan, penerimaan sebagian/penuh.
- **Sales Order**: draft, pengajuan, approval Admin, dan goods issue gudang.
- **Inventory**: stok per produk-gudang, riwayat Stock Movement, batas stok minimum, filter stok rendah, dan gambar produk opsional.
- **Laporan**: Admin melihat semua laporan; Sales melihat order miliknya; Petugas Gudang melihat stok dan ledger. Rentang tanggal berlaku untuk laporan order dan ledger. Saldo stok menampilkan kondisi saat ini.
- **API JSON**: `GET /api/products/{sku}/availability` untuk melihat stok tersedia produk.
- **Operasional**: Docker, healthcheck, Prometheus, Grafana, Alertmanager, SonarQube, Kubernetes/Helm manifest.

## Teknologi

- PHP 8.2 native OOP, PDO prepared statement, MySQL 8
- HTML, CSS, Vanilla JavaScript
- Docker Compose, PHPUnit, SonarQube

## Menjalankan aplikasi

```bash
docker compose up -d --build
```

Buka `http://localhost:8080`. Database tersedia pada `localhost:3307` untuk kebutuhan lokal.

Untuk instalasi baru, Compose memuat `database/schema-and-seed.sql`: 30 produk, dua gudang, dua akun Sales, dua akun Warehouse Staff, serta 13 PO dan 12 SO untuk data latihan. Verifikasi jumlah data dengan `database/verify-demo-seed.sql`.

| Peran demo pada instalasi baru | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.test` | `Admin123!` |
| Sales | `member1@example.test` | `Member123!` |
| Petugas Gudang | `warehouse1@example.test` | `Member123!` |

Akun ini hanya untuk data latihan dari seed. Ubah password sebelum menggunakan sistem di luar lingkungan latihan.

Jika database telah berisi data, jangan jalankan ulang `database/schema-and-seed.sql` karena skrip itu menjatuhkan dan membuat ulang tabel. Backup dulu, lalu tinjau migrasi yang belum diterapkan. Database aktif latihan ini sudah menjalankan migrasi ledger, kategori, dan constraint; skrip di `database/upgrade-*.sql` bersifat sekali jalan dan tidak boleh dijalankan ulang tanpa pemeriksaan skema.

Untuk menyiapkan contoh alur pada **database latihan yang sudah dibackup**, gunakan `docker compose exec -e ALLOW_DEMO_DATA=1 app php scripts/prepare-intermediate-demo.php`. Script ini menambah satu PO `PartiallyReceived` dan satu SO `PendingApproval` jika contohnya belum ada, tanpa mengubah order lama.

## Pemeriksaan operasional

```bash
docker compose ps
docker compose logs app --tail=50
curl http://localhost:8080/health/ready
curl http://localhost:8080/metrics
```

Untuk memeriksa stok rendah dari container aplikasi:

```bash
docker compose exec app php scripts/check-low-stock.php
```

## Pengujian

```bash
composer install
vendor/bin/phpunit
vendor/bin/phpstan analyse --debug --no-progress
```

Untuk pengujian di container:

```bash
docker compose -f docker-compose.test.yml build test
docker compose -f docker-compose.test.yml run --rm test
```

Pengujian integrasi transaksi menggunakan database MySQL sementara yang terpisah:

```bash
docker compose -p gudang-kita-integration -f docker-compose.integration.yml up --build --abort-on-container-exit --exit-code-from integration-test
docker compose -p gudang-kita-integration -f docker-compose.integration.yml down -v
```

## Struktur proyek

```text
app/          Controller, Service, Repository, Entity, Security, Validation
views/        Template UI
public/       Entry point, endpoint health, metrics, dan aset
database/     Schema, seed, serta upgrade database
docs/         ERD, ADR, scope, test scenario, dan dokumen DevOps
scripts/      Job operasional, termasuk pemeriksaan stok rendah
```

Konfigurasi rahasia disimpan di `.env` yang tidak dilacak Git. Gunakan `.env.example` sebagai acuan konfigurasi lokal.

## Keterbatasan yang diketahui

- [Checklist dua dokumen Final Project](docs/planning/checklist-final-project.md) memuat status bukti, kekurangan, dan langkah sebelum presentasi.
- Diagram initial historis belum tersedia; [catatan diagram awal](docs/planning/class-diagram-initial.md) menjelaskan keterbatasannya secara jujur.
- [Bukti responsif](docs/testing/screenshots/README.md) dan uji dari folder bersih sudah tersedia; video demo belum disiapkan. Perbaikan Sonar sudah ada di `main` dan dipindai ulang; tag `v1.0.0` masih menunjuk revisi sebelumnya.
- Alasan penolakan Sales Order belum disimpan sebagai field tersendiri.
