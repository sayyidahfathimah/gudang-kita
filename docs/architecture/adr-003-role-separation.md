# ADR 003 — Pemisahan Peran Transaksi

- **Status:** Diterima
- **Konteks:** Brief memisahkan pembuatan order, persetujuan, dan eksekusi gudang untuk menyediakan jejak tanggung jawab.
- **Keputusan:** Sales membuat dan mengajukan SO; Admin menyetujui SO; Warehouse Staff melakukan goods issue. Warehouse Staff membuat PO, mengubah Draft menjadi Ordered, dan mencatat penerimaan. Otorisasi diperiksa di server.
- **Konsekuensi:** Role tidak dapat melewati status workflow yang ditetapkan. Admin tetap memiliki akses master dan laporan. Pembatasan akses lintas gudang per akun belum diterapkan karena akun saat ini tidak memiliki relasi gudang.
