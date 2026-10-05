# Bukti Checklist Final Project — 6 Oktober 2026

Pemeriksaan tulis dilakukan pada `docker-compose.acceptance.yml` dengan project `gudang-checklist-acceptance`. Stack ini memakai database dan port sendiri (`127.0.0.1:18081`); database latihan aktif tidak diubah.

## Pemeriksaan yang lulus

| Area | Bukti yang dapat diulang | Hasil |
| --- | --- | --- |
| Login dan hak akses tiga role, CRUD user/produk, validasi dan CSRF | `python3 tests/http_acceptance.py` | 11 skenario lulus; termasuk penolakan Sales membuka PO dan halaman Admin. |
| Gambar produk | Skenario unggah HTTP dalam `tests/http_acceptance.py` | PNG valid tersimpan dengan nama acak; teks berkedok gambar dan berkas >2 MB ditolak. Batas PHP dibuat 3 MB agar pesan validasi aplikasi dapat tampil. |
| Pencarian PO/SO | Skenario HTTP pada tiga role | 10 baris per halaman; halaman kedua berbeda; search/status/sort bertahan; hasil kosong menampilkan pesan. |
| Produk dan gudang | `docker compose -p gudang-checklist-acceptance -f docker-compose.acceptance.yml exec -T acceptance-app php tests/checklist_evidence.php` | 30 produk × 2 gudang = 60 pasangan; detail produk memuat dua gudang dengan saldo berbeda dan total yang sesuai. |
| Dashboard dan laporan | Perintah PHP di atas pada seed yang sama | Total stok dashboard = jumlah baris laporan; status PO/SO dashboard = status pada ekspor order; ringkasan bulanan hanya Received/Fulfilled; seluruh ledger masuk rentang penuh. |
| Tampilan daftar/kosong | `python3 tests/capture_order_screenshots.py` | Delapan tangkapan layar PO/SO, desktop dan 360 px, tersedia di [indeks screenshot](screenshots/README.md). |
| File lokal dan Git | `git ls-files` dan `git log --all --name-only` dengan pola `.env`, `backups/`, `inventory_db*.sql`; `git grep -I -l -E` pada seluruh 11 commit untuk pola token GitHub, AWS key, private key, dan Sonar token; `rg -l` pada source saat ini | Remote `origin` milik akun peserta. Tidak ada file dengan nama atau pola secret tersebut; `ai-usage-log.md` tersedia. Pencarian pola tidak menjamin seluruh isi histori bebas secret/PII. |
| Unit test dan analisis statis | `vendor/bin/phpunit --testsuite Unit`; `vendor/bin/phpstan analyse --debug --no-progress` | 33 test/61 assertion lulus, termasuk factory PO/SO; PHPStan level 5 tanpa error. Mode `--debug` dipakai karena mode paralel tidak dapat membuka port lokal di sandbox. |
| Kontras dasar | Perhitungan WCAG terhadap putih untuk warna teks utama dan muted | Teks utama `#5d4650` 8,55:1; muted disesuaikan ke `#806d75` 4,81:1. Warna tombol utama pink tetap `#9d5a74` (5,06:1 terhadap putih). Ini belum audit semua kombinasi warna/keadaan kontrol. |
| Nama aksesibel form | `python3 tests/ui_label_audit.py` pada stack terpisah | 149 input/select/textarea pada 39 kombinasi halaman dan role diperiksa; semuanya memiliki label atau nama aksesibel. |
| Dependency order | `tests/Unit/ControllerFactoryTest.php`, uji HTTP, dan [diagram as-built](../architecture/class-diagram.md) | PO/SO Controller menerima dependency wajib dari factory; Service dan Controller memakai instance repository yang sama. Factory diuji tanpa database nyata. |

## Batas bukti

- Database latihan aktif semula memiliki 30 produk, 30 gudang, 93 baris stok, dan 807 pasangan kosong tanpa movement. Backup penuh dibuat di `backups/inventory_db_before_stock_pairs_20261006.sql` (di-ignore Git; 14 tabel, dump selesai dengan sukses). [Migrasi idempoten](../../database/ensure-stock-pairs.sql) menambah 807 pasangan tersebut dengan saldo nol. Pemeriksaan ulang menghasilkan 900 baris stok, 0 pasangan hilang, dan detail produk menampilkan 30 gudang. Skrip hanya menambah baris baru; saldo dan ledger lama tidak diubah. Asal movement lama yang tidak memiliki aktor tetap tidak dapat direkonstruksi.
- Hasil Sonar yang terdokumentasi berlaku untuk commit `7b84d44`. Perubahan setelah commit itu membutuhkan scan ulang; tag `v1.0.0` belum mewakili perubahan terbaru.
- Screenshot menunjukkan tampilan pada viewport yang disebutkan. Audit manual seluruh kontrol dan kontras di semua layar masih perlu dilakukan.
- Artefak diagram initial historis dan cuplikan kritik dari asesor belum tersedia, sehingga tidak dapat dinyatakan terverifikasi.
