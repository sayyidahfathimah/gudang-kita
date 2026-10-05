# Technical Debt

| Prioritas | Keterbatasan | Dampak | Perbaikan ideal |
| --- | --- | --- | --- |
| Sedang | Controller CRUD dan laporan masih membuat repository konkret; composition root baru mencakup PO/SO | Unit test controller di luar order sulit diisolasi | Perluas factory dan kontrak repository secara bertahap pada modul lain |
| Tinggi | Diagram initial historis tidak tersedia | Urutan desain sebelum coding tidak dapat dibuktikan | Lampirkan draft asli bila ditemukan; dokumentasikan evolusi desain pada proyek berikutnya sejak awal |
| Tinggi | Detail transaksi dan aktor sebelum penerapan ledger tidak tersedia | Penyesuaian `LEGACY-BASE-*` hanya membuktikan saldo awal bersih, bukan asal setiap perubahan lama | Pertahankan backup sumber, gunakan semua operasi stok baru melalui service, dan audit histori sumber jika ditemukan |
| Sedang | Alasan penolakan SO tidak disimpan terpisah | Riwayat keputusan Admin kurang lengkap | Tambah kolom alasan dan aktor penolak melalui migrasi aditif serta tampilkan di detail SO |
| Sedang | Laporan saldo stok mengambil seluruh baris sekaligus | Halaman lambat jika data tumbuh | Pagination pada query dan indeks sesuai filter |
| Rendah | Screenshot responsif sudah ada tetapi belum mencakup setiap role dan aksi | Perubahan layar lain masih bisa menimbulkan regresi | Tambah bukti dan pemeriksaan browser setelah setiap perubahan UI utama |
| Rendah | Gambar produk tidak dibuat thumbnail | File besar memperlambat halaman | Thumbnail WebP dan lazy loading |
