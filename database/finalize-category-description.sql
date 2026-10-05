-- Lanjutkan setelah upgrade-category-description.sql dan backup data aktif.
USE inventory_db;

UPDATE categories
SET description = CONCAT('Kelompok produk ', name, '.')
WHERE description IS NULL OR TRIM(description) = '';

ALTER TABLE categories MODIFY description TEXT NOT NULL;
