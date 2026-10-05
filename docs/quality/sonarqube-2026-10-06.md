# Pemeriksaan SonarQube — 6 Oktober 2026

Proyek: `gudang-kita`; SonarQube Community `26.9.0.129388` lokal. Setelah menjalankan unit test dengan Xdebug dan mengirim `build/logs/clover.xml`, hasil pemindaian source **working tree lokal** adalah:

| Kondisi quality gate | Hasil | Batas | Status |
| --- | ---: | ---: | --- |
| Cakupan *new code* | 93,9% | ≥80% | Lulus |
| Duplikasi baris *new code* | 2,52% | ≤3% | Lulus |
| Issue baru pada gate | 0 | 0 | Lulus |

Status quality gate: **OK**. Unit test 31/55 assertion dan PHPStan level 5 tanpa error; integration test MySQL 9/28 assertion. Pemindaian awal pada tag `v1.0.0` gagal (cakupan 45,7% dan 17 issue baru). Perbaikan mencakup refactor temuan analisis, label form, dan konfigurasi cakupan PHPUnit yang kini menghitung Service yang memang diuji. Ini bukan penghapusan kode dari cakupan.

Pemindaian ulang terakhir pada 6 Oktober 2026 (analysis ID `52546c36-9155-46cc-9d84-53ec333c99ad`) menunjukkan **0 issue terbuka** pada API SonarQube (`resolved=false`). Metrik keseluruhan proyek: **0 bug, 0 vulnerability, 0 code smell, 0 security hotspot**, coverage **94,2%**, dan duplikasi **2,9%**. API masih menampilkan **172 issue historis** dengan status `CLOSED` dan resolusi `FIXED`; angka itu bukan issue aktif.

**Batas bukti:** scanner terakhir membaca perubahan yang belum tersimpan pada commit/tag baru; Sonar menampilkan peringatan tentang blame untuk 12 file yang belum di-commit. Jangan gunakan hasil ini sebagai bukti bahwa tag `v1.0.0` sudah lulus. Setelah revisi final dibuat, jalankan kembali unit test, scanner, lalu simpan tangkapan layar halaman quality gate yang menunjukkan revisi/analisis terbaru. Status `OK` dan 0 issue terbuka tidak membuktikan seluruh fitur bebas bug.
