# ADR 002 — Perubahan Stok dan Ledger Atomik

- **Status:** Diterima
- **Konteks:** Penerimaan PO dan pengeluaran SO harus menjaga saldo, status order, dan riwayat movement tetap konsisten walau salah satu query gagal atau ada permintaan bersamaan.
- **Keputusan:** Jalankan perubahan detail, saldo stok, movement, dan status dalam satu transaksi InnoDB. Kunci order dan baris stok menggunakan `SELECT ... FOR UPDATE`; tolak barang keluar saat stok kurang.
- **Konsekuensi:** Kegagalan membatalkan seluruh perubahan dan menghindari stok negatif. Penguncian menambah durasi transaksi, sehingga proses di dalam transaksi harus tetap singkat.
