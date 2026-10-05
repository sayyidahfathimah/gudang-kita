-- Add zero-balance rows only for product–warehouse pairs with no stock row
-- and no historical movement. Existing balances and ledger entries stay intact.
-- Back up the active database before running this file.
START TRANSACTION;

INSERT INTO stocks (product_id, warehouse_id, stock_in, stock_out, current_stock)
SELECT p.id, w.id, 0, 0, 0
FROM products AS p
CROSS JOIN warehouses AS w
LEFT JOIN stocks AS s ON s.product_id = p.id AND s.warehouse_id = w.id
WHERE s.id IS NULL
  AND NOT EXISTS (
    SELECT 1 FROM stock_movements AS m
    WHERE m.product_id = p.id AND m.warehouse_id = w.id
  );

SELECT ROW_COUNT() AS zero_balance_pairs_added;
COMMIT;

SELECT COUNT(*) AS remaining_missing_pairs
FROM products AS p
CROSS JOIN warehouses AS w
LEFT JOIN stocks AS s ON s.product_id = p.id AND s.warehouse_id = w.id
WHERE s.id IS NULL;
