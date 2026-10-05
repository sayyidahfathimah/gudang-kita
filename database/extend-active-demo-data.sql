-- Idempotent additions for the existing database after the schema upgrade.
-- Adds product coverage and a second WarehouseStaff account without resetting data.
USE inventory_db;

START TRANSACTION;

INSERT INTO products(code, name, category_id, unit, purchase_price, selling_price, minimum_stock, status)
SELECT 'PRD030', 'Adaptor USB-C ke HDMI', c.id, 'pcs', 125000, 165000, 5, 'Active'
FROM categories c
WHERE c.status = 'Active'
  AND NOT EXISTS (SELECT 1 FROM products WHERE code = 'PRD030')
ORDER BY c.id
LIMIT 1;

INSERT INTO products(code, name, category_id, unit, purchase_price, selling_price, minimum_stock, status)
SELECT 'PRD031', 'Kabel Ethernet Cat 6 5 Meter', c.id, 'pcs', 45000, 65000, 10, 'Active'
FROM categories c
WHERE c.status = 'Active'
  AND NOT EXISTS (SELECT 1 FROM products WHERE code = 'PRD031')
ORDER BY c.id
LIMIT 1;

INSERT INTO users(username, password, name, email, role, is_active)
SELECT 'warehouse2', '$2y$12$EtyWrJYUPS7NwwRaNdKIZeB0fIhhTWHer.O4QIvPm.iIOIIzVrAsu', 'Petugas Gudang Dua', 'warehouse2@example.test', 'WarehouseStaff', 1
WHERE NOT EXISTS (SELECT 1 FROM users WHERE username = 'warehouse2' OR email = 'warehouse2@example.test');

INSERT INTO stocks(product_id, warehouse_id, stock_in, stock_out, current_stock)
SELECT p.id, w.id, 0, 0, 0
FROM products p
CROSS JOIN warehouses w
WHERE p.code IN ('PRD030', 'PRD031')
  AND NOT EXISTS (
      SELECT 1 FROM stocks s WHERE s.product_id = p.id AND s.warehouse_id = w.id
  );

COMMIT;
