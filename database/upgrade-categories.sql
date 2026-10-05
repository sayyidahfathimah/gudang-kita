USE inventory_db;

CREATE TABLE categories (
 id INT AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE,
 name VARCHAR(100) NOT NULL UNIQUE,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_categories_name(name)
) ENGINE=InnoDB;

INSERT INTO categories(code, name, status)
SELECT CONCAT('CAT', LPAD(ROW_NUMBER() OVER (ORDER BY category_name), 3, '0')), category_name, 'Active'
FROM (SELECT DISTINCT COALESCE(NULLIF(TRIM(category), ''), 'Umum') AS category_name FROM products) source;

ALTER TABLE products ADD COLUMN category_id INT NULL AFTER name;
UPDATE products p JOIN categories c ON c.name COLLATE utf8mb4_unicode_ci = COALESCE(NULLIF(TRIM(p.category), ''), 'Umum') COLLATE utf8mb4_unicode_ci SET p.category_id = c.id;
ALTER TABLE products MODIFY category_id INT NOT NULL, ADD INDEX idx_products_category(category_id), ADD CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT;
-- Kolom category lama dipertahankan sebagai data historis/rollback.
