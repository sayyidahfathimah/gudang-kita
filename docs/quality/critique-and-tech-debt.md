# Kritik Implementasi dan Technical Debt

## Kritik implementasi

- Pemisahan role Sales, Admin, dan Warehouse Staff serta transaksi stok dengan row lock memberi batas tanggung jawab yang mudah dijelaskan.
- Pemakaian repository/service belum merata. Beberapa controller masih membuat repository konkret sendiri, sehingga test controller belum mudah memakai fake.
- Upload gambar produk dibatasi tipe MIME, ukuran, dimensi, nama acak, dan folder non-executable. Belum ada resize/optimasi gambar ataupun penyimpanan di object storage.
- Tabel transaksi mendukung pagination dan filter di database. Sebagian laporan stok masih menampilkan keseluruhan saldo dan perlu pagination bila volume data bertambah.
- Seed demo menyertakan data awal dan status bervariasi untuk presentasi; data tersebut bukan data produksi.

## Technical debt

| Prioritas | Item | Dampak | Arah perbaikan |
| --- | --- | --- | --- |
| Tinggi | Dependency injection belum konsisten di semua controller | Unit test lapisan HTTP masih sulit diisolasi | Tambahkan composition root dan injeksikan kontrak repository/service |
| Tinggi | Belum ada suite automated browser E2E | Perjalanan login sampai transaksi perlu pemeriksaan manual | Tambahkan Playwright/Cypress di pipeline bila tool tersedia |
| Sedang | Pagination belum diterapkan pada semua tabel laporan/master terkait | Dataset besar dapat memperlambat render dan query | Gunakan pagination query dan indeks sesuai pola filter |
| Sedang | Data test belum memiliki coverage menyeluruh tiap controller dan role | Regresi otorisasi mungkin luput | Tambahkan test HTTP per role dan kasus negatif |
| Rendah | Gambar produk belum di-resize | File mendekati batas 2 MB dapat memperlambat halaman | Buat thumbnail WebP dan lazy loading |
