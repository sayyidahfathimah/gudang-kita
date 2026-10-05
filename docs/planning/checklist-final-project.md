# Checklist Final Project — Gudang Kita

**Acuan:** `Guidelines - Presentation Final Project (Peserta).pdf` (2 halaman) dan `Project Brief - Programmer.pdf` (19 halaman). Pemeriksaan lokal: **6 Oktober 2026**. Checklist ini membedakan fitur yang berjalan, bukti yang belum lengkap, dan tindakan yang harus dilakukan peserta sendiri. `✅` berarti diperiksa, `⚠️` berarti sebagian/menunggu bukti, dan `❌` berarti belum memenuhi. Perbaikan sudah dikirim ke `origin/main`; tag `v1.0.0` masih menunjuk revisi sebelum perbaikan.

## A. Guidelines — persiapan dan presentasi

| Checklist | Status | Bukti / tindakan |
| --- | --- | --- |
| Repository final dapat diakses asesor; tautan dikumpulkan lewat Microsoft Form paling lambat **4 Oktober 2026** | ⚠️ | Remote Git mengarah ke `sayyidahfathimah/gudang-kita`; tag `v1.0.0` tersedia. Pengiriman Form, akses asesor, dan ketepatan waktu tidak dapat diverifikasi dari repo. Pastikan commit yang dipresentasikan sama dengan yang dikumpulkan. |
| Pilih sesi presentasi 7, 8, atau 9 Oktober 2026 | ⚠️ | Konfirmasi voting/jadwal milik peserta; tidak ada bukti di repo. |
| Docker Compose menjalankan aplikasi dan MySQL dari folder bersih mengikuti README | ✅ | [README](../../README.md) dan [hasil pengujian](../testing/results-2026-10-05.md). Salinan bersih telah diuji; aplikasi dan DB aktif saat audit berstatus **healthy**. |
| Data demo aman, konsisten, mencakup alur utama dan pembatasan tiga role | ⚠️ | Seed bersih menyediakan 1 Admin, 2 Sales, 2 Warehouse Staff, 2 gudang, 30 produk, 25 order. Database latihan aktif memiliki contoh PO partial dan SO pending; detail ledger historis punya batasan pada bagian D. Hindari menampilkan `.env` atau kredensial aktif di layar. |
| Unit test, integration test, dan static analysis tersedia | ✅ | Saat audit: **31 unit/55 assertion**, **9 integration MySQL/28 assertion**, dan **PHPStan level 5: 0 error**. Perintah ada di [README](../../README.md); bukti sebelumnya di [hasil pengujian](../testing/results-2026-10-05.md). |
| SonarQube lulus dan hasilnya dipahami | ⚠️ | Pemindaian commit `ae399ac` di `main`: quality gate `OK`, *new code coverage* **93,9%**, duplikasi baru **2,52%**, dan **0 issue terbuka**. Tunjukkan hasilnya di SonarQube; tag `v1.0.0` masih lama, jadi jangan mengaitkan hasil ini dengan tag tersebut. |
| Source, skema, query, Docker, class diagram, ADR, dan refactor siap dibuka | ✅ | [Skema](../../database/schema-and-seed.sql), [diagram as-built](../architecture/class-diagram.md), [ADR](../architecture/adr-002-atomic-stock-ledger.md), [refactor log](../quality/refactor-log.md), [Docker Compose](../../docker-compose.yml). |
| Screenshot atau video cadangan | ⚠️ | [Screenshot](../testing/screenshots/README.md) desktop/360 px untuk lima halaman ada. Rekaman alur PO → SO → ledger tiga role belum tersedia. |
| Presentasi maksimal **10 menit** + 10 menit tanya jawab | ⚠️ | Jadwal yang berlaku untuk peserta ada pada *Guidelines*: pembukaan 1 menit, demo 4, teknis 3, kualitas 1, refleksi 1. Latihan dengan timer harus dilakukan peserta. Durasi 12–15 menit pada *Brief* adalah uraian demo umum; ikuti batas 10 menit pada dokumen presentasi. |
| Jelaskan kontribusi, keputusan kode, batasan, dan penggunaan AI secara terbuka | ⚠️ | [AI usage log](../../ai-usage-log.md) tersedia. Penjelasan tanpa bantuan AI dan respons terhadap cuplikan kode assessor hanya dapat dibuktikan saat presentasi. |

**Urutan demo 10 menit:** login Sales → buat/ajukan SO → Admin approve → Petugas Gudang issue → tunjukkan saldo dan ledger; lanjutkan PO partial receipt, satu kasus stok kurang, dashboard tiga role, lalu source transaksi, test, Sonar, dan batasan. Siapkan satu produk dengan stok berbeda di dua gudang serta dua halaman hasil pencarian.

## B. Project Brief — fungsi dan data

| Kode / checklist | Status | Bukti / catatan |
| --- | --- | --- |
| Teknologi: PHP 8.2 native OOP, HTML/CSS/Vanilla JS + Fetch, MySQL 8, tanpa framework/ORM/DI container | ✅ | [Composer](../../composer.json), [Dockerfile](../../Dockerfile), [source](../../app), [aset frontend](../../public). |
| AUTH-01/02: login email, hash, session, tiga role, logout, akses terlindung | ✅ | Skenario login, session rotation, password salah, logout, dan akses tanpa sesi di [uji HTTP](../../tests/http_acceptance.py). |
| USR-01: CRUD/aktif-nonaktif user, email unik, akses Admin saja | ✅ | Uji HTTP duplikat email, CRUD, dan respons 403 untuk Sales/Gudang. |
| PRD-01: produk/kategori, SKU unik, harga/reorder point, nonaktif, gambar opsional | ⚠️ | CRUD, harga negatif, dan nonaktif teruji. Uji upload file valid/tidak valid serta batas ukuran belum dicatat sebagai bukti. |
| WH-01: stok per pasangan produk–gudang; total/detail | ⚠️ | Seed baru membuat semua pasangan dan detail stok tersedia. Database **lama yang aktif** masih memiliki pasangan tanpa baris stok; jangan klaim seluruh pasangan aktif lengkap tanpa rekonsiliasi/backup. Demo dua gudang dengan saldo berbeda masih perlu direkam. |
| PO-01: Draft → Ordered → partial/full receipt, stok dan ledger atomik | ✅ | [Service PO](../../app/Service/PurchaseOrderService.php), [integration test](../../tests/DatabaseIntegration/InventoryWorkflowTest.php), contoh PO partial pada DB latihan. |
| SO-01: Draft → PendingApproval → Approved → Fulfilled/Cancelled; Sales tidak boleh approve; issue menolak stok kurang | ✅ | [Service SO](../../app/Service/SalesOrderService.php), [integration test](../../tests/DatabaseIntegration/InventoryWorkflowTest.php), matriks role di [uji HTTP](../../tests/http_acceptance.py). Alasan penolakan belum disimpan khusus. |
| VIEW-01: list/detail/empty state produk, PO, SO per role | ⚠️ | Daftar/detail dan empty state katalog teruji; screenshot empty state PO/SO belum tersedia. |
| FIND-01: search, filter, sort, pagination 10/page, filter bertahan; seed 30 produk/25 order | ⚠️ | Seed minimum tersedia dan katalog dua halaman teruji. Kombinasi search/filter/sort serta perpindahan halaman **PO dan SO** belum tercatat dalam uji yang sama; demonstrasikan dan simpan bukti. |
| DASH-01: agregasi nyata sesuai tiga role | ✅ | Angka Admin dan antrean Sales/Gudang dicocokkan dengan query database pada [audit](project-brief-compliance.md). |
| REPORT-01: CSV ledger/status order, dua rentang tanggal, hak akses role | ⚠️ | Unduhan dua rentang dan role teruji. Kesamaan definisi angka CSV dan dashboard untuk semua kondisi transaksi belum diaudit menyeluruh. |
| API-01: JSON dengan auth dan HTTP 200/401/404 | ✅ | Endpoint availability dan respons tiga status telah diperiksa; [dokumentasi API](../../README.md). |
| VAL-01/ERR-01: validasi server/client, pesan aman, isian bertahan, 403/404/503 | ✅ | Uji HTTP invalid form/CSRF/404 dan simulasi MySQL gagal tanpa bocor stack trace dicatat di [audit](project-brief-compliance.md). |
| UI-01: desktop/360 px, label, focus, tabel tidak terpotong | ⚠️ | [Screenshot](../testing/screenshots/README.md) tersedia. Audit manual semua form/order, label, fokus keyboard, dan kontras belum lengkap; beberapa label telah diperbaiki pada perubahan lokal ini. |
| DB-01: relasi, index/constraint, prepared query, transaksi, seed dari kosong | ✅ | [Schema](../../database/schema-and-seed.sql), [ERD](erd.md), uji MySQL sementara. Jangan jalankan ulang seed destruktif pada database aktif. |
| JOB-01: script low-stock mandiri | ✅ | [Script](../../scripts/check-low-stock.php) telah dijalankan manual lewat Docker. |

## C. Project Brief — desain, kualitas, dan penyerahan

| Kode / checklist | Status | Bukti / catatan |
| --- | --- | --- |
| ARCH-01: Controller → Service → Repository, interface MySQL+fake, constructor injection | ⚠️ | Service PO/SO memakai interface dan fake dalam unit test. Beberapa controller masih membuat repository konkret; [technical debt](../quality/tech-debt.md) mencatat perlunya composition root yang konsisten. Minimum interface dua implementasi terpenuhi. |
| ARCH-02: transaksi receipt/issue dan perlindungan oversell | ✅ | `FOR UPDATE`, commit/rollback, dan skenario dua issue pada [integration test](../../tests/DatabaseIntegration/InventoryWorkflowTest.php). |
| DESIGN-01: class diagram **initial sebelum coding** dan as-built sesuai kode | ❌ | [As-built](../architecture/class-diagram.md) telah dicocokkan lagi dengan kode PO/SO, interface, fake, dan enum. [Rancangan retrospektif](class-diagram-initial.md) membantu penjelasan tetapi artefak historis awal belum ditemukan; diagram baru tidak bisa menggantikannya. |
| DESIGN-02: 2–3 ADR | ✅ | [ADR layer](../architecture/adr-001-layered-boundaries.md), [transaksi](../architecture/adr-002-atomic-stock-ledger.md), [role](../architecture/adr-003-role-separation.md). |
| DESIGN-03: ≥3 refactor entries, audit SRP, debt, commit `refactor:` | ✅ | [Refactor log](../quality/refactor-log.md), [debt](../quality/tech-debt.md), commit `e60898f`. |
| DESIGN-04: kritik smell/SOLID atas cuplikan assessor | ⚠️ | [Latihan critique](../quality/critique.md) ada; cuplikan aktual assessor baru dapat dinilai saat defense. |
| TEST-01: ≥6 unit case pada ≥3 area, tanpa DB nyata | ✅ | 31 unit/55 assertion, termasuk validator dan service dengan fake. |
| TEST-02: ≥3 integration MySQL Docker | ✅ | 9 test/28 assertion pada MySQL 8 terisolasi. |
| TEST-03: PHPStan 5+ nol critical, test independen | ✅ | PHPStan level 5: 0 error; unit/integration lulus pada container terpisah. |
| Docker clean start, `.env.example`, README, app+DB service | ✅ | Salinan folder bersih dan login/alur utama sudah diuji; [README](../../README.md), [Compose](../../docker-compose.yml). |
| Git individual, tanpa secret/PII aktif dalam repo/history, AI disclosure | ⚠️ | `.env` dan backup di-ignore; pemindaian pola pada history tidak menemukan kandidat. Ini bukan jaminan ketiadaan seluruh secret. Tinjau ulang backup/screenshot/seed sebelum menyerahkan repo publik; [AI log](../../ai-usage-log.md) ada. |
| Paket dokumentasi, data minimum demo, tag/release final | ⚠️ | Struktur docs, skema, test dan tag `v1.0.0` ada. Perbaikan Sonar terbaru sudah di `main`, tetapi belum berada di tag; jika penilaian memakai tag, buat tag rilis baru setelah review peserta. |
| Peserta mampu menjelaskan alur data, satu ADR, refactor, transaksi, query, dan batasan | ⚠️ | Ini perlu latihan dan pembuktian lisan saat defense. Nilai akhir ≥80 dan bebas *critical failure* diputuskan assessor, bukan hasil audit repo. |

## D. Pekerjaan sebelum dinyatakan sepenuhnya siap

1. **Sinkronkan revisi final:** `main` sudah berisi perbaikan dan Sonar lulus pada commit `ae399ac`. Pastikan tautan submission menunjuk `main` terbaru. Jika penilaian memakai tag, buat tag baru dari revisi terbaru dan jangan menilai tag `v1.0.0` memakai hasil scan `main`.
2. **Lengkapi bukti demo:** screenshot empty state PO/SO, upload gambar tidak valid, filter/sort/pagination PO/SO, alur tiga role, dua gudang berbeda, serta CSV dan dashboard pada data yang sama.
3. **Periksa data lama secara aman:** buat backup sebelum membuat baris stok produk–gudang yang belum ada; tetapkan saldo awal dan ledger yang dapat ditelusuri. Movement historis tanpa aktor asli tidak boleh direka. [Batas rekonsiliasi](project-brief-compliance.md) menjelaskan data aktif.
4. **Cari artefak diagram initial yang benar-benar dibuat sebelum coding.** Jika tidak ada, sampaikan sebagai kekurangan kepada assessor.
5. **Peserta:** pastikan link Form dan aksesnya, sesi presentasi, latihan 10 menit, serta penjelasan penggunaan AI. Tanggal pengumpulan dalam *Guidelines* sudah lewat pada saat audit ini; repo tidak dapat membuktikan apakah pengumpulan dilakukan tepat waktu.

Kubernetes, Helm, Grafana, dan Alertmanager boleh ditunjukkan sebagai tambahan, tetapi **bukan pengganti** requirement wajib pada *Project Brief* (§4.3).
