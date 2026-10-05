-- Jalankan satu kali pada inventory_db yang sudah ada setelah membuat backup.
-- Tidak mengubah kategori atau transaksi lama.
USE inventory_db;

ALTER TABLE categories ADD COLUMN description TEXT NULL AFTER name;
