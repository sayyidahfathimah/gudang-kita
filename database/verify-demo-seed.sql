USE inventory_db;

SELECT 'products' AS dataset, COUNT(*) AS row_count, IF(COUNT(*) >= 30, 'PASS', 'CHECK') AS minimum_check FROM products
UNION ALL SELECT 'warehouses', COUNT(*), IF(COUNT(*) >= 2, 'PASS', 'CHECK') FROM warehouses
UNION ALL SELECT 'sales_accounts', COUNT(*), IF(COUNT(*) >= 2, 'PASS', 'CHECK') FROM users WHERE role = 'Sales'
UNION ALL SELECT 'warehouse_accounts', COUNT(*), IF(COUNT(*) >= 2, 'PASS', 'CHECK') FROM users WHERE role = 'WarehouseStaff'
UNION ALL SELECT 'purchase_orders', COUNT(*), IF(COUNT(*) >= 10, 'PASS', 'CHECK') FROM purchase_orders
UNION ALL SELECT 'sales_orders', COUNT(*), IF(COUNT(*) >= 10, 'PASS', 'CHECK') FROM sales_orders;
