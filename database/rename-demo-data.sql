-- Jalankan sekali pada database aktif untuk mengganti data seed lama yang memakai kata "Demo".
USE inventory_db;

UPDATE customers
SET name = CONCAT('PT Cakrawala Niaga ', CAST(SUBSTRING(code, 4) AS UNSIGNED))
WHERE name LIKE 'Customer Demo %';

UPDATE products
SET name = CONCAT('Peralatan Operasional ', CAST(SUBSTRING(name, 13) AS UNSIGNED)),
    category = 'ATK'
WHERE name LIKE 'Produk Demo %';

UPDATE suppliers
SET name = CONCAT('PT Mitra Distribusi ', CAST(SUBSTRING(name, 15) AS UNSIGNED))
WHERE name LIKE 'Supplier Demo %';

UPDATE warehouses
SET name = CONCAT('Gudang Regional ', CAST(SUBSTRING(name, 13) AS UNSIGNED))
WHERE name LIKE 'Gudang Demo %';

UPDATE projects
SET name = CONCAT('Optimalisasi Operasional ', CAST(SUBSTRING(name, 14) AS UNSIGNED)),
    description = 'Peningkatan proses operasional cabang.'
WHERE name LIKE 'Project Demo %';

UPDATE tasks
SET title = CONCAT('Persiapan Operasional ', CAST(SUBSTRING(title, 11) AS UNSIGNED)),
    description = 'Menyelesaikan aktivitas operasional sesuai rencana.'
WHERE title LIKE 'Task Demo %';

UPDATE purchase_orders
SET notes = 'Pengadaan persediaan reguler'
WHERE notes = 'Data demo';

UPDATE sales_orders
SET notes = 'Penjualan reguler kepada customer'
WHERE notes = 'Data demo';
