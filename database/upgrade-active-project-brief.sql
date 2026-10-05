-- One-time, additive upgrade for the existing inventory_db.
-- Take a fresh backup before running. This script preserves current rows.
-- Run only when the preflight confirms these columns/constraints are missing.
USE inventory_db;

ALTER TABLE products
    ADD COLUMN image_path VARCHAR(255) NULL AFTER minimum_stock;

ALTER TABLE stock_movements
    -- Existing users.id in this live database is signed INT, so actor type matches it.
    ADD COLUMN created_by INT NULL AFTER qty,
    MODIFY transaction_type ENUM('IN','OUT','Receipt','Issue','Adjustment') NOT NULL;

-- Preserve historical meaning while converting legacy names to canonical types.
UPDATE stock_movements
SET transaction_type = CASE transaction_type
    WHEN 'IN' THEN 'Receipt'
    WHEN 'OUT' THEN 'Issue'
    ELSE transaction_type
END;

ALTER TABLE stock_movements
    MODIFY transaction_type ENUM('Receipt','Issue','Adjustment') NOT NULL,
    ADD INDEX idx_sm_actor(created_by),
    ADD CONSTRAINT fk_sm_created_by FOREIGN KEY(created_by)
        REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE products ADD CONSTRAINT chk_product_values
    CHECK (purchase_price >= 0 AND selling_price >= 0 AND minimum_stock >= 0);
ALTER TABLE purchase_order_details ADD CONSTRAINT chk_pod_values
    CHECK (qty > 0 AND received_qty >= 0 AND received_qty <= qty AND price >= 0);
ALTER TABLE sales_order_details ADD CONSTRAINT chk_sod_values
    CHECK (qty > 0 AND price >= 0);
ALTER TABLE stocks ADD CONSTRAINT chk_stock_totals
    CHECK (stock_in >= 0 AND stock_out >= 0 AND current_stock >= 0);
ALTER TABLE stock_movements ADD CONSTRAINT chk_sm_qty CHECK (qty > 0);
