# Checklist Final Project — Gudang Kita

**Acuan:** `Guidelines - Presentation Final Project (Peserta).pdf` (2 halaman) dan `Project Brief - Programmer.pdf` (19 halaman). Pemeriksaan lokal: **6 Oktober 2026**. Checklist kerja ini berfokus pada bukti teknis; rincian administrasi dan jadwal pada PDF asli tidak disalin ke sini. Status **Terverifikasi** berarti ada bukti pemeriksaan, **Perlu bukti** berarti implementasi atau demonstrasinya belum diperiksa lengkap, dan **Belum tersedia** berarti artefak yang diwajibkan belum ditemukan. Rilis teknis `v1.0.1` menunjuk revisi yang diuji; dua bukti historis/eksternal di bawah masih terbuka.

## A. Guidelines — bukti teknis

| Checklist | Status | Bukti / tindakan |
| --- | --- | --- |
| Docker Compose menjalankan aplikasi dan MySQL dari folder bersih mengikuti README | Terverifikasi | [README](../../README.md) dan [hasil pengujian](../testing/results-2026-10-05.md). Salinan bersih telah diuji; aplikasi dan DB aktif saat audit berstatus **healthy**. |
| Data demo aman, konsisten, mencakup alur utama dan pembatasan tiga role | Terverifikasi | Seed bersih menyediakan 1 Admin, 2 Sales, 2 Warehouse Staff, 2 gudang, 30 produk, 25 order. Login dan pembatasan role diuji lewat [HTTP acceptance](../../tests/http_acceptance.py); konsistensi stok/order pada seed ada di [bukti checklist](../testing/checklist-evidence-2026-10-06.md). Data aktif lama memiliki batasan pada bagian D. |
| Unit test, integration test, dan static analysis tersedia | Terverifikasi | Saat audit: **33 unit/61 assertion**, **9 integration MySQL/28 assertion** pada audit sebelumnya, dan **PHPStan level 5: 0 error**. [Bukti terbaru](../testing/checklist-evidence-2026-10-06.md) dan [hasil sebelumnya](../testing/results-2026-10-05.md). |
| SonarQube lulus dan hasilnya dipahami | Terverifikasi | [Pemindaian source terbaru](../quality/sonarqube-2026-10-06.md): quality gate `OK`, *new code coverage* **93,9%**, duplikasi baru **2,30%**, dan **0 issue terbuka**. Scan ulang setelah commit diperlukan agar Sonar menampilkan revisi Git yang sama dengan rilis. |
| Source, skema, query, Docker, class diagram, ADR, dan refactor siap dibuka | Terverifikasi | [Skema](../../database/schema-and-seed.sql), [diagram as-built](../architecture/class-diagram.md), [ADR](../architecture/adr-002-atomic-stock-ledger.md), [refactor log](../quality/refactor-log.md), [Docker Compose](../../docker-compose.yml). |
| Screenshot cadangan | Terverifikasi | [Screenshot](../testing/screenshots/README.md) desktop/360 px untuk sembilan keadaan halaman tersedia. |

**Urutan demonstrasi teknis:** login Sales → buat/ajukan SO → Admin approve → Petugas Gudang issue → tunjukkan saldo dan ledger; lanjutkan PO partial receipt, satu kasus stok kurang, dashboard tiga role, lalu source transaksi, test, Sonar, dan batasan. Siapkan satu produk dengan stok berbeda di dua gudang serta dua halaman hasil pencarian.

## B. Project Brief — fungsi dan data

| Kode / checklist | Status | Bukti / catatan |
| --- | --- | --- |
| Teknologi: PHP 8.2 native OOP, HTML/CSS/Vanilla JS + Fetch, MySQL 8, tanpa framework/ORM/DI container | Terverifikasi | [Composer](../../composer.json), [Dockerfile](../../Dockerfile), [source](../../app), [aset frontend](../../public). |
| AUTH-01/02: login email, hash, session, tiga role, logout, akses terlindung | Terverifikasi | Skenario login, session rotation, password salah, logout, dan akses tanpa sesi di [uji HTTP](../../tests/http_acceptance.py). |
| USR-01: CRUD/aktif-nonaktif user, email unik, akses Admin saja | Terverifikasi | Uji HTTP duplikat email, CRUD, dan respons 403 untuk Sales/Gudang. |
| PRD-01: produk/kategori, SKU unik, harga/reorder point, nonaktif, gambar opsional | Terverifikasi | CRUD, validasi harga negatif, nonaktif, dan unggah PNG valid/format salah/>2 MB lulus pada [11 uji HTTP](../../tests/http_acceptance.py). File produk memakai nama acak; [bukti](../testing/checklist-evidence-2026-10-06.md). |
| WH-01: stok per pasangan produk–gudang; total/detail | Terverifikasi | Seed baru: 30 × 2 = 60 pasangan dan dua saldo berbeda cocok dengan total. Database aktif: backup dibuat, [migrasi](../../database/ensure-stock-pairs.sql) menambah 807 pasangan kosong dengan saldo nol; pemeriksaan ulang 30 × 30 = 900 baris, tanpa pasangan hilang. [Bukti](../testing/checklist-evidence-2026-10-06.md). |
| PO-01: Draft → Ordered → partial/full receipt, stok dan ledger atomik | Terverifikasi | [Service PO](../../app/Service/PurchaseOrderService.php), [integration test](../../tests/DatabaseIntegration/InventoryWorkflowTest.php), contoh PO partial pada DB latihan. |
| SO-01: Draft → PendingApproval → Approved → Fulfilled/Cancelled; Sales tidak boleh approve; issue menolak stok kurang | Terverifikasi | [Service SO](../../app/Service/SalesOrderService.php), [integration test](../../tests/DatabaseIntegration/InventoryWorkflowTest.php), matriks role di [uji HTTP](../../tests/http_acceptance.py). Alasan penolakan belum disimpan khusus. |
| VIEW-01: list/detail/empty state produk, PO, SO per role | Terverifikasi | Katalog dan role diuji via HTTP; daftar dan keadaan kosong PO/SO ada di [screenshot](../testing/screenshots/README.md) pada desktop dan 360 px. |
| FIND-01: search, filter, sort, pagination 10/page, filter bertahan; seed 30 produk/25 order | Terverifikasi | [Uji HTTP](../../tests/http_acceptance.py) mencakup katalog, PO, SO, tiga role, dua halaman, status, pencarian, urutan, dan filter yang bertahan. |
| DASH-01: agregasi nyata sesuai tiga role | Terverifikasi | Angka Admin dan antrean Sales/Gudang dicocokkan dengan query database pada [audit](project-brief-compliance.md). |
| REPORT-01: CSV ledger/status order, dua rentang tanggal, hak akses role | Terverifikasi | Unduhan rentang dan role teruji sebelumnya. [Audit pada seed yang sama](../testing/checklist-evidence-2026-10-06.md) mencocokkan total stok, setiap status PO/SO, ringkasan bulanan Received/Fulfilled, ledger, dan rentang kosong. |
| API-01: JSON dengan auth dan HTTP 200/401/404 | Terverifikasi | Endpoint availability dan respons tiga status telah diperiksa; [dokumentasi API](../../README.md). |
| VAL-01/ERR-01: validasi server/client, pesan aman, isian bertahan, 403/404/503 | Terverifikasi | Uji HTTP invalid form/CSRF/404 dan simulasi MySQL gagal tanpa bocor stack trace dicatat di [audit](project-brief-compliance.md). |
| UI-01: desktop/360 px, label, focus, tabel tidak terpotong | Terverifikasi | [Screenshot](../testing/screenshots/README.md) desktop/360 px untuk sembilan keadaan, tabel di panel scroll, dan `:focus-visible` tersedia. [Audit](../testing/checklist-evidence-2026-10-06.md) memeriksa 149 kontrol pada 39 halaman/role serta kontras warna teks dasar. Periksa ulang bila layar baru ditambahkan. |
| DB-01: relasi, index/constraint, prepared query, transaksi, seed dari kosong | Terverifikasi | [Schema](../../database/schema-and-seed.sql), [ERD](erd.md), uji MySQL sementara. Jangan jalankan ulang seed destruktif pada database aktif. |
| JOB-01: script low-stock mandiri | Terverifikasi | [Script](../../scripts/check-low-stock.php) telah dijalankan manual lewat Docker. |

## C. Project Brief — desain, kualitas, dan penyerahan

| Kode / checklist | Status | Bukti / catatan |
| --- | --- | --- |
| ARCH-01: Controller → Service → Repository, interface MySQL+fake, constructor injection | Terverifikasi | Workflow PO/SO memakai [factory manual](../../app/Support/ControllerFactory.php) untuk menginjeksi repository dan service wajib; [unit test](../../tests/Unit/ControllerFactoryTest.php) membuktikan instance yang sama, dan HTTP tetap lulus. Service memakai interface dengan implementasi MySQL+fake. Modul CRUD lain masih punya [technical debt](../quality/tech-debt.md). |
| ARCH-02: transaksi receipt/issue dan perlindungan oversell | Terverifikasi | `FOR UPDATE`, commit/rollback, dan skenario dua issue pada [integration test](../../tests/DatabaseIntegration/InventoryWorkflowTest.php). |
| DESIGN-01: class diagram **initial sebelum coding** dan as-built sesuai kode | Belum tersedia | [As-built](../architecture/class-diagram.md) telah dicocokkan lagi dengan kode PO/SO, interface, fake, dan enum. [Rancangan retrospektif](class-diagram-initial.md) membantu penjelasan tetapi artefak historis awal belum ditemukan; diagram baru tidak bisa menggantikannya. |
| DESIGN-02: 2–3 ADR | Terverifikasi | [ADR layer](../architecture/adr-001-layered-boundaries.md), [transaksi](../architecture/adr-002-atomic-stock-ledger.md), [role](../architecture/adr-003-role-separation.md). |
| DESIGN-03: ≥3 refactor entries, audit SRP, debt, commit `refactor:` | Terverifikasi | [Refactor log](../quality/refactor-log.md), [debt](../quality/tech-debt.md), commit `e60898f`. |
| DESIGN-04: kritik smell/SOLID atas cuplikan assessor | Perlu bukti | [Latihan critique](../quality/critique.md) ada; cuplikan aktual assessor baru dapat dinilai saat defense. |
| TEST-01: ≥6 unit case pada ≥3 area, tanpa DB nyata | Terverifikasi | 33 unit/61 assertion, termasuk validator, service dengan fake, dan factory order tanpa DB. |
| TEST-02: ≥3 integration MySQL Docker | Terverifikasi | 9 test/28 assertion pada MySQL 8 terisolasi. |
| TEST-03: PHPStan 5+ nol critical, test independen | Terverifikasi | PHPStan level 5: 0 error; unit/integration lulus pada container terpisah. |
| Docker clean start, `.env.example`, README, app+DB service | Terverifikasi | Salinan folder bersih dan login/alur utama sudah diuji; [README](../../README.md), [Compose](../../docker-compose.yml). |
| Git individual, tanpa secret/PII aktif dalam repo/history, AI disclosure | Terverifikasi | Remote `origin` adalah repo pribadi peserta. `.env` dan backup di-ignore; [pemeriksaan path dan pola](../testing/checklist-evidence-2026-10-06.md) pada 11 commit dan source saat ini tidak menemukan kandidat token/private key/PII aktif. [AI log](../../ai-usage-log.md) ada. Pemindaian pola tidak menjamin semua bentuk rahasia terdeteksi. |
| Paket dokumentasi, data minimum demo, tag/release final | Terverifikasi | README, dokumentasi, seed 30 produk/25 order, test, screenshot, dan tag rilis `v1.0.1` tersedia pada revisi yang sama. Dua bukti DESIGN-01/04 tetap dinyatakan terbuka, bukan disamarkan oleh tag. |

## D. Pekerjaan sebelum dinyatakan sepenuhnya siap

1. **Gunakan revisi rilis yang benar:** tag `v1.0.1` berisi perbaikan dan bukti 6 Oktober. Tag `v1.0.0` adalah revisi lama; hasil Sonar terbaru tidak berlaku untuk tag lama itu.
2. **Lengkapi pemeriksaan manual:** bukti otomatis untuk empty state PO/SO, upload gambar, filter/sort/pagination, tiga role, dua gudang berbeda, angka laporan, dan 149 label form ada di [hasil 6 Oktober](../testing/checklist-evidence-2026-10-06.md). Audit manual kombinasi warna/keyboard yang belum dicakup serta isi histori Git untuk secret/PII.
3. **Catat batas data lama:** backup dan 807 pasangan stok nol sudah diselesaikan tanpa mengubah saldo lama. Movement historis tanpa aktor asli tidak boleh direka. [Batas rekonsiliasi](project-brief-compliance.md) menjelaskan data aktif.
4. **Cari artefak diagram initial yang benar-benar dibuat sebelum coding.** Jika tidak ada, sampaikan sebagai kekurangan kepada assessor.

Kubernetes, Helm, Grafana, dan Alertmanager boleh ditunjukkan sebagai tambahan, tetapi **bukan pengganti** requirement wajib pada *Project Brief* (§4.3).
