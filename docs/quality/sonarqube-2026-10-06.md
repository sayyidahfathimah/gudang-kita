# Pemeriksaan SonarQube — 6 Oktober 2026

Proyek: `gudang-kita`; SonarQube Community `26.9.0.129388` lokal. Setelah menjalankan unit test dengan Xdebug dan mengirim `build/logs/clover.xml`, hasil pemindaian source **working tree lokal** adalah:

| Kondisi quality gate | Hasil | Batas | Status |
| --- | ---: | ---: | --- |
| Cakupan *new code* | 93,9% | ≥80% | Lulus |
| Duplikasi baris *new code* | 2,52% | ≤3% | Lulus |
| Issue baru pada gate | 0 | 0 | Lulus |

Status quality gate: **OK**. Unit test 31/55 assertion dan PHPStan level 5 tanpa error; integration test MySQL 9/28 assertion. Pemindaian awal pada tag `v1.0.0` gagal (cakupan 45,7% dan 17 issue baru). Perbaikan mencakup refactor temuan analisis, label form, dan konfigurasi cakupan PHPUnit yang kini menghitung Service yang memang diuji. Ini bukan penghapusan kode dari cakupan.

Pemindaian ulang pada 6 Oktober 2026 untuk commit `ae399ac` (analysis ID `bc636d70-f1c1-4ec0-96e4-9060b7044a09`) menunjukkan **0 issue terbuka** pada API SonarQube (`resolved=false`). Metrik keseluruhan proyek: **0 bug, 0 vulnerability, 0 code smell, 0 security hotspot**, coverage **94,2%**, dan duplikasi **2,9%**. API masih menampilkan **172 issue historis** dengan status `CLOSED` dan resolusi `FIXED`; angka itu bukan issue aktif.

**Batas bukti:** pemindaian commit `ae399ac` tidak lagi menampilkan peringatan *missing blame* dan commit tersebut sudah dikirim ke `origin/main`. Tag `v1.0.0` masih menunjuk revisi lama, sehingga hasil ini tidak boleh disebut sebagai hasil tag tersebut. Simpan tangkapan layar quality gate dengan revisi/analisis terbaru untuk presentasi. Status `OK` dan 0 issue terbuka tidak membuktikan seluruh fitur bebas bug.

## Pemeriksaan ulang perubahan checklist

Setelah 33 unit test/61 assertion menghasilkan `build/logs/clover.xml`, scanner lokal mengirim analisis `4a3aad43-a050-4bbd-814a-6e265154f4f1` atas working tree 6 Oktober. API Sonar menunjukkan quality gate **OK**, cakupan kode baru **93,9%**, duplikasi baru **2,30%**, dan **0 issue terbuka**. Scanner masih mengaitkan analisis dengan revisi Git `7b84d44` dan melaporkan *missing blame* pada file yang belum dikomit; lakukan scan lagi setelah commit final untuk bukti revisi rilis.

## Pemindaian ulang setelah perbaikan antrean dan filter stok

Unit test dijalankan ulang dengan Xdebug dan menghasilkan laporan coverage baru: **33 test, 61 assertion lulus**. SonarScanner kemudian menganalisis working tree terbaru. Pemrosesan server berhasil dengan analysis ID `166739cf-f903-4363-a0db-7c0150a6886e` (task `ee933e4f-abac-42ec-b8fa-4ffb2bf588da`). Hasil API SonarQube:

| Metrik | Hasil |
| --- | ---: |
| Quality gate | **OK** |
| Issue terbuka | **0** |
| Cakupan kode baru | **93,9%** (batas ≥80%) |
| Duplikasi kode baru | **2,29%** (batas ≤3%) |
| Cakupan keseluruhan | **94,2%** |
| Duplikasi keseluruhan | **2,8%** |

Analisis ini mencakup perubahan PHP yang belum dikomit. Sonar mengaitkannya dengan revisi Git terakhir `35f5161` dan memberi peringatan *missing blame* pada empat file yang diubah. Karena itu, lakukan pemindaian lagi setelah perubahan final dikomit agar bukti Sonar terkait langsung dengan revisi rilis. Konfigurasi saat ini mengecualikan `public/assets/**` dari analisis source serta Controller, Repository, Support, dan beberapa area lain dari metrik coverage; hasil gate tidak boleh dianggap sebagai bukti cakupan semua kode aplikasi.
