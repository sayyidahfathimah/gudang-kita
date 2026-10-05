-- One-time upgrade for the existing training database, after a full backup.
-- A baseline Adjustment bridges the difference between today's stock balance
-- and the surviving legacy ledger. It is not a reconstructed PO/SO or actor.
-- The script never changes stocks, existing movements, or order statuses.
USE inventory_db;

ALTER TABLE stock_movements
    ADD COLUMN adjustment_direction ENUM('Increase','Decrease') NULL AFTER transaction_type,
    ADD COLUMN note VARCHAR(255) NULL AFTER transaction_number,
    ADD CONSTRAINT chk_sm_adjustment_direction CHECK (
        (transaction_type = 'Adjustment' AND adjustment_direction IS NOT NULL)
        OR (transaction_type <> 'Adjustment' AND adjustment_direction IS NULL)
    );

CREATE TEMPORARY TABLE legacy_stock_reconciliation AS
SELECT s.id AS stock_id, s.product_id, s.warehouse_id,
       ROUND(s.current_stock - COALESCE(l.net_qty, 0), 2) AS delta
FROM stocks s
LEFT JOIN (
    SELECT product_id, warehouse_id,
           SUM(CASE
               WHEN transaction_type = 'Receipt' THEN qty
               WHEN transaction_type = 'Issue' THEN -qty
               WHEN transaction_type = 'Adjustment' AND adjustment_direction = 'Increase' THEN qty
               WHEN transaction_type = 'Adjustment' AND adjustment_direction = 'Decrease' THEN -qty
               ELSE 0
           END) AS net_qty
    FROM stock_movements
    GROUP BY product_id, warehouse_id
) l ON l.product_id = s.product_id AND l.warehouse_id = s.warehouse_id
WHERE ABS(s.current_stock - COALESCE(l.net_qty, 0)) >= 0.005;

INSERT INTO stock_movements
    (transaction_type, adjustment_direction, transaction_id, transaction_number,
     note, product_id, warehouse_id, qty, created_by, movement_date)
SELECT 'Adjustment', IF(r.delta > 0, 'Increase', 'Decrease'), 0,
       CONCAT('LEGACY-BASE-', r.stock_id),
       'Rekonsiliasi saldo awal; rincian transaksi dan aktor historis tidak tersedia',
       r.product_id, r.warehouse_id, ABS(r.delta), NULL, NOW()
FROM legacy_stock_reconciliation r
WHERE NOT EXISTS (
    SELECT 1 FROM stock_movements m
    WHERE m.transaction_type = 'Adjustment'
      AND m.transaction_number = CONCAT('LEGACY-BASE-', r.stock_id)
);

DROP TEMPORARY TABLE legacy_stock_reconciliation;
