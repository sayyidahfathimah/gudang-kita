USE inventory_db;

UPDATE products p
JOIN categories c ON c.name COLLATE utf8mb4_unicode_ci = COALESCE(NULLIF(TRIM(p.category), ''), 'Umum') COLLATE utf8mb4_unicode_ci
SET p.category_id = c.id;
ALTER TABLE products
    MODIFY category_id INT NOT NULL,
    ADD INDEX idx_products_category(category_id),
    ADD CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT;
