# AI Usage Log

AI was used as an engineering assistant for refactoring the existing inventory project into the required Project Activity & Task Management System, generating boilerplate, reviewing requirements, and preparing test/documentation structure.

Participant responsibilities:
- Review every generated change.
- Verify implementation against the Project Brief and syllabus.
- Run tests and Docker validation.
- Understand and be able to explain the architecture and business rules during technical defense.

No client credentials, production secrets, or private company data are included in this project.

## 5 Oktober 2026 — Penyesuaian Project Brief Intermediate

- Tool: OpenAI Codex.
- Tujuan/prompt pengguna (ringkasan): memperbaiki bagian aplikasi Gudang Kita yang belum sesuai brief Intermediate tanpa merusak data aktif.
- Output yang digunakan setelah review: akses laporan per role, query Sales Order terbatas pembuat, deskripsi kategori, tipe ledger domain, dokumentasi as-built, dan konfigurasi PHPStan.
- Output/pendekatan yang ditolak: menjalankan ulang schema-and-seed pada database aktif karena skrip itu menghapus tabel; menyatakan diagram initial historis tersedia tanpa bukti; menyatakan screenshot dan clone test lulus tanpa pemeriksaan.
- Verifikasi: backup sebelum migrasi, PHPStan level 5 tanpa error, PHPUnit unit dan integration pada MySQL sementara, serta HTTP login/CSV tiga peran. Bukti ringkas tercatat di `docs/testing/results-2026-10-05.md`.
- Tanggung jawab peserta: meninjau perubahan, menyiapkan demonstrasi, dan menjelaskan alur serta keputusan desain saat defense.

## 5 Oktober 2026 — Penyelesaian bukti dan rekonsiliasi

- Permintaan: melengkapi kekurangan brief dan melanjutkan pekerjaan tanpa merusak aplikasi.
- Perubahan: migrasi Adjustment saldo awal dengan catatan keterbatasan historis; contoh PO partial dan SO pending melalui service; perbaikan rute detail stok serta layout mobile; screenshot lima halaman; dokumentasi audit dan alur terbaru.
- Keputusan: tidak mengarang aktor/receipt historis dan tidak menjalankan ulang seed destruktif pada database aktif. Backup dilakukan sebelum rekonsiliasi.
- Bukti: instalasi Docker bersih, login tiga role, API/CSV, unit 31 test/55 assertion, integration MySQL 9 test/28 assertion, PHPStan level 5 tanpa error.
- Refactor terisolasi: e60898f, ekstraksi paginationLink dengan dua test perilaku dan escaping.
- Keterbatasan: draft desain awal asli dan cuplikan assessor belum tersedia; hasil pemeriksaan tidak menjamin seluruh kemungkinan bug tidak ada.

## 6 Oktober 2026 — Verifikasi HTTP dan perbaikan form

- Tujuan: menutup celah bukti hak akses, validasi, dan katalog tanpa mengubah data aktif.
- Perubahan: pesan email duplikat tepat pada field, isian user serta PO/SO dipertahankan saat gagal, katalog produk diberi pencarian/filter/sort/pagination.
- Verifikasi: sembilan skenario HTTP pada MySQL sementara lulus, termasuk CRUD produk dan pesan harga negatif; PHPUnit unit 31 test/55 assertion dan PHPStan level 5 tanpa error.
- Batas: delapan angka dashboard Admin cocok dengan query database, Sales 02 menampilkan SO miliknya, dan antrean Gudang sesuai order aktif. Simulasi MySQL putus pada container terpisah mengonfirmasi HTTP 503 tanpa bocoran SQLSTATE/PDOException.

- Pemeriksaan Git: `.env`/backup tidak terlacak, lima commit serta 178 file kerja diperiksa untuk pola token/private key dan tidak ada kandidat. Pemindaian pola tidak membuktikan ketiadaan semua rahasia.

## 6 Oktober 2026 — Checklist dua dokumen final project

- Tool: OpenAI Codex.
- Tujuan/prompt pengguna (ringkasan): menyusun checklist dari Guidelines Presentation Final Project dan Project Brief Intermediate serta memeriksa kesesuaian aplikasi.
- Output yang digunakan: checklist status dan bukti per requirement, perbaikan temuan Sonar pada source lokal, label form, dan perluasan cakupan PHPUnit ke Service yang diuji.
- Output yang ditolak: mengklaim tag `v1.0.0` sudah lulus berdasarkan scan working tree; membuat diagram initial historis palsu; mengubah database aktif tanpa backup.
- Verifikasi: 31 unit/55 assertion, 9 integration MySQL/28 assertion, 9 skenario HTTP, PHPStan level 5 tanpa error, Sonar quality gate lokal `OK`. Keterbatasan dan tindak lanjut dicatat pada `docs/planning/checklist-final-project.md`.
