# Refactoring Log

| Perubahan | Alasan | Hasil |
| --- | --- | --- |
| Memisahkan controller dan repository | Memisahkan HTTP flow dari query database | Struktur layered lebih mudah ditelusuri. |
| Menggunakan PDO prepared statement | Menghindari query dengan input langsung | Input database lebih aman. |
| Menggunakan transaksi pada PO dan SO | Header, detail, stok, dan ledger harus konsisten | Perubahan gagal akan di-rollback. |
| Menambah validasi stok sebelum SO Completed | Mencegah constraint database tampil ke user | Oversell ditolak dengan pesan stok yang jelas. |
| Menambah role Petugas Gudang | Memenuhi pembatasan tiga role | Akses operasional gudang terpisah dari Admin dan Member. |
| Membuat kode master otomatis | Menghindari kode duplikat dan edit manual | Produk, customer, supplier, serta gudang memakai format kode yang konsisten. |
