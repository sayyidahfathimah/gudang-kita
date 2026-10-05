-- Jalankan sekali setelah semua baris IN/OUT historis menjadi Receipt/Issue.
-- Backup database aktif lebih dulu; skrip ini tidak menghapus baris.
USE inventory_db;

ALTER TABLE stock_movements
    MODIFY transaction_type ENUM('Receipt','Issue','Adjustment') NOT NULL;
