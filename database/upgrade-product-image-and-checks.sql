-- Upgrade non-destructive untuk database yang sudah berjalan.
-- Jalankan setelah membuat backup dan pastikan versi MySQL mendukung CHECK enforcement (MySQL 8.0.16+).
USE inventory_db;

ALTER TABLE products ADD COLUMN image_path VARCHAR(255) NULL AFTER minimum_stock;
ALTER TABLE products ADD CONSTRAINT chk_product_values
    CHECK (purchase_price >= 0 AND selling_price >= 0 AND minimum_stock >= 0);
ALTER TABLE purchase_order_details ADD CONSTRAINT chk_pod_values
    CHECK (qty > 0 AND received_qty >= 0 AND received_qty <= qty AND price >= 0);
ALTER TABLE sales_order_details ADD CONSTRAINT chk_sod_values
    CHECK (qty > 0 AND price >= 0);
ALTER TABLE stocks ADD CONSTRAINT chk_stock_totals
    CHECK (stock_in >= 0 AND stock_out >= 0 AND current_stock >= 0);
ALTER TABLE stock_movements ADD CONSTRAINT chk_sm_qty CHECK (qty > 0);

-- `chk_stocks_non_negative` sudah ditambahkan pada migrasi role dan transaksi intermediate.
