# Refactoring Log

| Perubahan | Alasan | Hasil |
| --- | --- | --- |
| Memisahkan controller dan repository | Memisahkan HTTP flow dari query database | Struktur layered lebih mudah ditelusuri. |
| Menggunakan PDO prepared statement | Menghindari query dengan input langsung | Input database lebih aman. |
| Menggunakan transaksi pada PO dan SO | Header, detail, stok, dan ledger harus konsisten | Perubahan gagal akan di-rollback. |
| Menambah validasi stok sebelum SO Fulfilled | Mencegah constraint database tampil ke user | Oversell ditolak dengan pesan stok yang jelas. |
| Menambah role Petugas Gudang | Memenuhi pembatasan tiga role | Akses operasional gudang terpisah dari Admin, Sales, dan Petugas Gudang. |
| Membuat kode master otomatis | Menghindari kode duplikat dan edit manual | Produk, customer, supplier, serta gudang memakai format kode yang konsisten. |
| Menambahkan state machine SO dan PO | Alur transaksi perlu dibatasi per role serta mendukung penerimaan sebagian | SO mengikuti Draft → PendingApproval → Approved → Fulfilled; PO mencatat penerimaan sebagian per detail. |
| Menambah API stok dan ekspor CSV | Brief membutuhkan integrasi JSON dan output laporan yang dapat diunduh | Endpoint stok JSON dan CSV laporan stok tersedia untuk pengguna yang telah login. |
