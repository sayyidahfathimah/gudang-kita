CREATE DATABASE IF NOT EXISTS inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE inventory_db;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS stocks;
DROP TABLE IF EXISTS purchase_order_details;
DROP TABLE IF EXISTS purchase_orders;
DROP TABLE IF EXISTS sales_order_details;
DROP TABLE IF EXISTS sales_orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS warehouses;
DROP TABLE IF EXISTS tasks;
DROP TABLE IF EXISTS projects;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 username VARCHAR(50) NOT NULL UNIQUE,
 password VARCHAR(255) NOT NULL,
 name VARCHAR(100) NOT NULL,
 email VARCHAR(150) NOT NULL UNIQUE,
 role ENUM('Admin','Sales','WarehouseStaff') NOT NULL DEFAULT 'Sales',
 is_active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE projects (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(150) NOT NULL,
 description TEXT NOT NULL,
 status ENUM('Planning','Active','Completed','Archived') NOT NULL DEFAULT 'Planning',
 start_date DATE NOT NULL,
 target_date DATE NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_projects_status(status), INDEX idx_projects_target(target_date),
 CONSTRAINT chk_project_dates CHECK(target_date >= start_date)
) ENGINE=InnoDB;

CREATE TABLE tasks (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 project_id INT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,
 description TEXT NOT NULL,
 assignee_id INT UNSIGNED NOT NULL,
 status ENUM('To Do','In Progress','Done') NOT NULL DEFAULT 'To Do',
 priority ENUM('Low','Medium','High') NOT NULL DEFAULT 'Medium',
 due_date DATE NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_tasks_project(project_id), INDEX idx_tasks_assignee_status(assignee_id,status), INDEX idx_tasks_due_status(due_date,status),
 CONSTRAINT fk_tasks_project FOREIGN KEY(project_id) REFERENCES projects(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_tasks_assignee FOREIGN KEY(assignee_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE categories (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE,
 name VARCHAR(100) NOT NULL UNIQUE,
 description TEXT NOT NULL,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_categories_name(name)
) ENGINE=InnoDB;

CREATE TABLE products (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 category_id INT UNSIGNED NOT NULL,
 unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
 purchase_price DECIMAL(15,2) NOT NULL DEFAULT 0,
 selling_price DECIMAL(15,2) NOT NULL DEFAULT 0,
 minimum_stock DECIMAL(15,2) NOT NULL DEFAULT 0,
 image_path VARCHAR(255) NULL,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_products_name(name), INDEX idx_products_status(status), INDEX idx_products_category(category_id),
 CONSTRAINT fk_products_category FOREIGN KEY(category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT chk_product_values CHECK(purchase_price >= 0 AND selling_price >= 0 AND minimum_stock >= 0)
) ENGINE=InnoDB;

CREATE TABLE suppliers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 address TEXT NULL,
 phone VARCHAR(40) NULL,
 email VARCHAR(150) NULL,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_suppliers_name(name)
) ENGINE=InnoDB;

CREATE TABLE customers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 address TEXT NULL,
 phone VARCHAR(40) NULL,
 email VARCHAR(150) NULL,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_customers_name(name)
) ENGINE=InnoDB;

CREATE TABLE warehouses (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 code VARCHAR(40) NOT NULL UNIQUE,
 name VARCHAR(150) NOT NULL,
 address TEXT NULL,
 phone VARCHAR(40) NULL,
 status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_warehouses_name(name)
) ENGINE=InnoDB;

CREATE TABLE purchase_orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 po_number VARCHAR(50) NOT NULL UNIQUE,
 supplier_id INT UNSIGNED NOT NULL,
 warehouse_id INT UNSIGNED NOT NULL,
 po_date DATE NOT NULL,
 status ENUM('Draft','Ordered','PartiallyReceived','Received','Cancelled') NOT NULL DEFAULT 'Draft',
 notes TEXT NULL,
 total_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_po_supplier(supplier_id), INDEX idx_po_date(po_date), INDEX idx_po_status(status),
 CONSTRAINT fk_po_supplier FOREIGN KEY(supplier_id) REFERENCES suppliers(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_po_warehouse FOREIGN KEY(warehouse_id) REFERENCES warehouses(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE purchase_order_details (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 purchase_order_id INT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NOT NULL,
 qty DECIMAL(15,2) NOT NULL,
 received_qty DECIMAL(15,2) NOT NULL DEFAULT 0,
 price DECIMAL(15,2) NOT NULL DEFAULT 0,
 subtotal DECIMAL(18,2) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_pod_po(purchase_order_id), INDEX idx_pod_product(product_id),
 CONSTRAINT fk_pod_po FOREIGN KEY(purchase_order_id) REFERENCES purchase_orders(id) ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT fk_pod_product FOREIGN KEY(product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT chk_pod_values CHECK(qty > 0 AND received_qty >= 0 AND received_qty <= qty AND price >= 0)
) ENGINE=InnoDB;

CREATE TABLE sales_orders (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 so_number VARCHAR(50) NOT NULL UNIQUE,
 customer_id INT UNSIGNED NOT NULL,
 created_by INT UNSIGNED NULL,
 approved_by INT UNSIGNED NULL,
 warehouse_id INT UNSIGNED NOT NULL,
 so_date DATE NOT NULL,
 status ENUM('Draft','PendingApproval','Approved','Fulfilled','Cancelled') NOT NULL DEFAULT 'Draft',
 notes TEXT NULL,
 total_amount DECIMAL(18,2) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_so_customer(customer_id), INDEX idx_so_date(so_date), INDEX idx_so_status(status), INDEX idx_so_created_by(created_by), INDEX idx_so_approved_by(approved_by),
 CONSTRAINT fk_so_customer FOREIGN KEY(customer_id) REFERENCES customers(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_so_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
 CONSTRAINT fk_so_approved_by FOREIGN KEY(approved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
 CONSTRAINT fk_so_warehouse FOREIGN KEY(warehouse_id) REFERENCES warehouses(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE sales_order_details (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 sales_order_id INT UNSIGNED NOT NULL,
 product_id INT UNSIGNED NOT NULL,
 qty DECIMAL(15,2) NOT NULL,
 price DECIMAL(15,2) NOT NULL DEFAULT 0,
 subtotal DECIMAL(18,2) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_sod_so(sales_order_id), INDEX idx_sod_product(product_id),
 CONSTRAINT fk_sod_so FOREIGN KEY(sales_order_id) REFERENCES sales_orders(id) ON UPDATE CASCADE ON DELETE CASCADE,
 CONSTRAINT fk_sod_product FOREIGN KEY(product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT chk_sod_values CHECK(qty > 0 AND price >= 0)
) ENGINE=InnoDB;

CREATE TABLE stocks (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 product_id INT UNSIGNED NOT NULL,
 warehouse_id INT UNSIGNED NOT NULL,
 stock_in DECIMAL(15,2) NOT NULL DEFAULT 0,
 stock_out DECIMAL(15,2) NOT NULL DEFAULT 0,
 current_stock DECIMAL(15,2) NOT NULL DEFAULT 0,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_stock_product_warehouse(product_id,warehouse_id),
 CONSTRAINT chk_stocks_non_negative CHECK(current_stock >= 0),
 CONSTRAINT fk_stock_product FOREIGN KEY(product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_stock_warehouse FOREIGN KEY(warehouse_id) REFERENCES warehouses(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT chk_stock_totals CHECK(stock_in >= 0 AND stock_out >= 0 AND current_stock >= 0)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 transaction_type ENUM('Receipt','Issue','Adjustment') NOT NULL,
 adjustment_direction ENUM('Increase','Decrease') NULL,
 transaction_id INT UNSIGNED NOT NULL,
 transaction_number VARCHAR(50) NOT NULL,
 note VARCHAR(255) NULL,
 product_id INT UNSIGNED NOT NULL,
 warehouse_id INT UNSIGNED NOT NULL,
 qty DECIMAL(15,2) NOT NULL,
 created_by INT UNSIGNED NULL,
 movement_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_sm_product_date(product_id,movement_date), INDEX idx_sm_warehouse_date(warehouse_id,movement_date), INDEX idx_sm_transaction(transaction_type,transaction_id),
 CONSTRAINT fk_sm_product FOREIGN KEY(product_id) REFERENCES products(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_sm_warehouse FOREIGN KEY(warehouse_id) REFERENCES warehouses(id) ON UPDATE CASCADE ON DELETE RESTRICT,
 CONSTRAINT fk_sm_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL,
 CONSTRAINT chk_sm_qty CHECK(qty > 0),
 CONSTRAINT chk_sm_adjustment_direction CHECK (
  (transaction_type = 'Adjustment' AND adjustment_direction IS NOT NULL)
  OR (transaction_type <> 'Adjustment' AND adjustment_direction IS NULL)
 )
) ENGINE=InnoDB;

INSERT INTO users(username,password,name,email,role,is_active) VALUES
('admin', '$2y$12$EzaiZWlZupPg4bC81bkmVeBiu5VUEAtxvhyIAs9LGdM.bxFe.tUw6', 'Administrator', 'admin@example.test', 'Admin', 1),
('member1', '$2y$12$yfIZUXvwqO6zDrTBzT8fNOqCpRt1TaXi9sU.mxi4YHNeHNihU4kB.', 'Member One', 'member1@example.test', 'Sales', 1),
('sales02', '$2y$12$yfIZUXvwqO6zDrTBzT8fNOqCpRt1TaXi9sU.mxi4YHNeHNihU4kB.', 'Sales 02', 'sales02@example.test', 'Sales', 1),
('warehouse1', '$2y$12$yfIZUXvwqO6zDrTBzT8fNOqCpRt1TaXi9sU.mxi4YHNeHNihU4kB.', 'Petugas Gudang Satu', 'warehouse1@example.test', 'WarehouseStaff', 1),
('warehouse2', '$2y$12$yfIZUXvwqO6zDrTBzT8fNOqCpRt1TaXi9sU.mxi4YHNeHNihU4kB.', 'Petugas Gudang Dua', 'warehouse2@example.test', 'WarehouseStaff', 1);

INSERT INTO projects(name,description,status,start_date,target_date) VALUES
('Website Revamp','Redesign and improve the company public website.','Active','2026-09-01','2026-10-15'),
('Data Migration','Migrate legacy reporting data to the new platform.','Planning','2026-09-15','2026-11-15'),
('Mobile App Release','Prepare the mobile application release and QA activities.','Completed','2026-07-01','2026-08-31');

INSERT INTO tasks(project_id,title,description,assignee_id,status,priority,due_date) VALUES
(1,'Prepare wireframe','Create approved wireframe for the landing page.',2,'Done','High','2026-09-05'),
(1,'Build navigation','Implement responsive navigation.',2,'In Progress','High','2026-09-27'),
(1,'Create hero section','Build hero section based on approved design.',3,'To Do','Medium','2026-09-30'),
(1,'Optimize images','Compress and optimize website images.',2,'To Do','Low','2026-10-02'),
(1,'Implement contact form','Create and validate contact form.',3,'To Do','Medium','2026-10-04'),
(1,'Accessibility review','Review keyboard navigation and labels.',2,'To Do','High','2026-10-06'),
(1,'Cross browser test','Test latest Chrome, Safari and Firefox.',3,'To Do','Medium','2026-10-08'),
(1,'SEO metadata','Add title and metadata to main pages.',2,'To Do','Low','2026-10-09'),
(1,'Performance audit','Measure page performance and fix bottlenecks.',3,'To Do','High','2026-10-11'),
(1,'Release candidate','Prepare release candidate for stakeholder review.',2,'To Do','High','2026-10-13'),
(2,'Source inventory','List all legacy data sources.',3,'Done','Medium','2026-09-18'),
(2,'Define mapping','Map source fields to target fields.',2,'In Progress','High','2026-09-28'),
(2,'Create staging schema','Prepare staging tables.',3,'To Do','High','2026-10-03'),
(2,'Build extraction','Implement extraction process.',2,'To Do','High','2026-10-08'),
(2,'Build transformation','Implement transformation rules.',3,'To Do','Medium','2026-10-12'),
(2,'Data quality rules','Define quality checks.',2,'To Do','Medium','2026-10-15'),
(2,'Reconciliation','Compare source and target totals.',3,'To Do','High','2026-10-20'),
(2,'UAT preparation','Prepare UAT dataset and checklist.',2,'To Do','Medium','2026-10-25'),
(2,'UAT support','Support business UAT activities.',3,'To Do','Medium','2026-10-30'),
(2,'Migration sign-off','Prepare final migration sign-off.',2,'To Do','High','2026-11-10'),
(3,'Finalize requirements','Confirm final release scope.',2,'Done','High','2026-07-05'),
(3,'Implement login','Complete authentication screens.',3,'Done','High','2026-07-10'),
(3,'Implement profile','Complete profile settings.',2,'Done','Medium','2026-07-15'),
(3,'Push notification QA','Validate push notification scenarios.',3,'Done','Medium','2026-07-22'),
(3,'Regression testing','Execute release regression test.',2,'Done','High','2026-08-01'),
(3,'Security review','Review mobile app security checklist.',3,'Done','High','2026-08-05'),
(3,'Store assets','Prepare application store assets.',2,'Done','Low','2026-08-10'),
(3,'Release notes','Write release notes.',3,'Done','Low','2026-08-15'),
(3,'Production smoke test','Run production smoke test.',2,'Done','High','2026-08-25'),
(3,'Release retrospective','Document lessons learned.',3,'Done','Low','2026-08-30');

INSERT INTO categories(code,name,description,status) VALUES
('CAT001','Elektronik','Perangkat elektronik dan komponen utama.','Active'),
('CAT002','Aksesoris','Perlengkapan pendukung perangkat kerja.','Active'),
('CAT003','Kabel','Kabel data, jaringan, dan tampilan.','Active'),
('CAT004','ATK','Alat tulis dan kebutuhan kantor.','Active');

INSERT INTO products(code,name,category_id,unit,purchase_price,selling_price,minimum_stock,status) VALUES
('PRD001','Laptop Lenovo ThinkPad',1,'pcs',8500000,9500000,2,'Active'),
('PRD002','Keyboard Logitech K120',2,'pcs',180000,250000,5,'Active'),
('PRD003','Mouse Wireless',2,'pcs',120000,175000,5,'Active'),
('PRD004','Kabel HDMI 2 Meter',3,'pcs',45000,75000,10,'Active'),
('PRD005','Kertas A4 80gsm',4,'rim',50000,65000,10,'Active'),
('PRD006','Headset Gaming',2,'pcs',300000,450000,3,'Active');
INSERT INTO products(code,name,category_id,unit,purchase_price,selling_price,minimum_stock,status) VALUES
('PRD007','Flashdisk 64 GB',1,'pcs',85000,115000,5,'Active'),
('PRD008','SSD Eksternal 1 TB',1,'pcs',950000,1150000,2,'Active'),
('PRD009','Webcam Full HD',1,'pcs',275000,350000,3,'Active'),
('PRD010','Router WiFi Dual Band',1,'pcs',420000,525000,3,'Active'),
('PRD011','Kabel USB Type C',3,'pcs',25000,40000,10,'Active'),
('PRD012','Kabel LAN Cat 6',3,'meter',7000,12000,20,'Active'),
('PRD013','Adaptor USB-C 65W',1,'pcs',185000,245000,4,'Active'),
('PRD014','Mouse Pad Ergonomis',2,'pcs',35000,55000,8,'Active'),
('PRD015','Monitor LED 24 Inch',1,'pcs',1650000,1950000,2,'Active'),
('PRD016','Stand Laptop Aluminium',2,'pcs',95000,145000,5,'Active'),
('PRD017','Tinta Printer Hitam',4,'pcs',110000,145000,6,'Active'),
('PRD018','Tinta Printer Warna',4,'pcs',125000,165000,6,'Active'),
('PRD019','Map Dokumen Folio',4,'pcs',5000,8500,25,'Active'),
('PRD020','Pulpen Gel Hitam',4,'box',28000,40000,10,'Active'),
('PRD021','Kertas Label A4',4,'pak',32000,48000,8,'Active'),
('PRD022','Speaker Desktop USB',2,'pcs',135000,185000,4,'Active'),
('PRD023','Kamera CCTV Indoor',1,'pcs',325000,425000,3,'Active'),
('PRD024','Switch Jaringan 8 Port',1,'pcs',250000,325000,3,'Active'),
('PRD025','Kabel DisplayPort',3,'pcs',55000,80000,6,'Active'),
('PRD026','Baterai UPS 12V',1,'pcs',210000,285000,4,'Active'),
('PRD027','Kalkulator Meja',4,'pcs',65000,90000,5,'Active'),
('PRD028','Buku Agenda A5',4,'pcs',18000,28000,12,'Active'),
('PRD029','Kursi Kerja Ergonomis',2,'pcs',1250000,1550000,2,'Active'),
('PRD030','Label Pengiriman Thermal',4,'roll',22000,35000,10,'Active');

INSERT INTO suppliers(code,name,address,phone,email,status) VALUES
('SUP001','PT Sumber Elektronik','Jakarta','021-5551001','sales@sumber.test','Active'),
('SUP002','CV ATK Nusantara','Bogor','0251-5552002','sales@atk.test','Active'),
('SUP003','PT Teknologi Jaya','Depok','021-5553003','sales@tekjaya.test','Active');

INSERT INTO customers(code,name,address,phone,email,status) VALUES
('CUS001','PT Maju Bersama','Jakarta','021-6001001','purchasing@maju.test','Active'),
('CUS002','CV Sejahtera','Bogor','0251-6002002','admin@sejahtera.test','Active'),
('CUS003','PT Digital Indonesia','Depok','021-6003003','procurement@digital.test','Active');

INSERT INTO warehouses(code,name,address,phone,status) VALUES
('WH001','Gudang Utama','Bogor','0251-7001001','Active'),
('WH002','Gudang Cabang','Depok','021-7002002','Active');

INSERT INTO stocks(product_id,warehouse_id,stock_in,stock_out,current_stock)
SELECT p.id,w.id,CASE WHEN p.id % 7=0 OR p.id=4 OR p.id=6 THEN 2 ELSE 20 END,0,
 CASE WHEN p.id % 7=0 OR p.id=4 OR p.id=6 THEN 2 ELSE 20 END
FROM products p CROSS JOIN warehouses w;

INSERT INTO stock_movements(transaction_type,transaction_id,transaction_number,product_id,warehouse_id,qty,created_by,movement_date)
SELECT 'Receipt',0,CONCAT('OPENING-',p.code,'-',w.code),p.id,w.id,s.current_stock,NULL,NOW()
FROM stocks s JOIN products p ON p.id=s.product_id JOIN warehouses w ON w.id=s.warehouse_id;

INSERT INTO purchase_orders(po_number,supplier_id,warehouse_id,po_date,status,notes,total_amount)
WITH RECURSIVE sequence_numbers(n) AS (SELECT 1 UNION ALL SELECT n+1 FROM sequence_numbers WHERE n<13)
SELECT CONCAT('PO-SEED-',LPAD(n,3,'0')),((n-1)%3)+1,((n-1)%2)+1,DATE_SUB(CURDATE(),INTERVAL n DAY),
 CASE n WHEN 1 THEN 'Ordered' WHEN 2 THEN 'PartiallyReceived' WHEN 3 THEN 'Received' WHEN 4 THEN 'Cancelled' ELSE 'Draft' END,
 'Data latihan Purchase Order',0
FROM sequence_numbers;

INSERT INTO purchase_order_details(purchase_order_id,product_id,qty,received_qty,price,subtotal)
SELECT po.id,p.id,5,CASE po.status WHEN 'PartiallyReceived' THEN 2 WHEN 'Received' THEN 5 ELSE 0 END,p.purchase_price,5*p.purchase_price
FROM purchase_orders po JOIN products p ON p.id=((po.id-1)%30)+1 WHERE po.po_number LIKE 'PO-SEED-%';
UPDATE purchase_orders po SET total_amount=(SELECT COALESCE(SUM(d.subtotal),0) FROM purchase_order_details d WHERE d.purchase_order_id=po.id) WHERE po.po_number LIKE 'PO-SEED-%';
INSERT INTO stock_movements(transaction_type,transaction_id,transaction_number,product_id,warehouse_id,qty,created_by,movement_date)
SELECT 'Receipt',po.id,po.po_number,d.product_id,po.warehouse_id,d.received_qty,NULL,NOW()
FROM purchase_orders po JOIN purchase_order_details d ON d.purchase_order_id=po.id WHERE po.po_number LIKE 'PO-SEED-%' AND d.received_qty>0;
UPDATE stocks s JOIN purchase_orders po ON po.warehouse_id=s.warehouse_id JOIN purchase_order_details d ON d.purchase_order_id=po.id AND d.product_id=s.product_id
SET s.stock_in=s.stock_in+d.received_qty,s.current_stock=s.current_stock+d.received_qty WHERE po.po_number LIKE 'PO-SEED-%' AND d.received_qty>0;

INSERT INTO sales_orders(so_number,customer_id,created_by,approved_by,warehouse_id,so_date,status,notes,total_amount)
WITH RECURSIVE sequence_numbers(n) AS (SELECT 1 UNION ALL SELECT n+1 FROM sequence_numbers WHERE n<12)
SELECT CONCAT('SO-SEED-',LPAD(n,3,'0')),((n-1)%3)+1,IF(n%2=0,2,3),IF(n IN (3,4),1,NULL),((n-1)%2)+1,DATE_SUB(CURDATE(),INTERVAL n DAY),
 CASE n WHEN 1 THEN 'Draft' WHEN 2 THEN 'PendingApproval' WHEN 3 THEN 'Approved' WHEN 4 THEN 'Fulfilled' WHEN 5 THEN 'Cancelled' ELSE 'Draft' END,
 'Data latihan Sales Order',0
FROM sequence_numbers;

INSERT INTO sales_order_details(sales_order_id,product_id,qty,price,subtotal)
SELECT so.id,p.id,1,p.selling_price,p.selling_price FROM sales_orders so JOIN products p ON p.id=((so.id-1)%30)+1 WHERE so.so_number LIKE 'SO-SEED-%';
UPDATE sales_orders so SET total_amount=(SELECT COALESCE(SUM(d.subtotal),0) FROM sales_order_details d WHERE d.sales_order_id=so.id) WHERE so.so_number LIKE 'SO-SEED-%';
INSERT INTO stock_movements(transaction_type,transaction_id,transaction_number,product_id,warehouse_id,qty,created_by,movement_date)
SELECT 'Issue',so.id,so.so_number,d.product_id,so.warehouse_id,d.qty,NULL,NOW()
FROM sales_orders so JOIN sales_order_details d ON d.sales_order_id=so.id WHERE so.so_number LIKE 'SO-SEED-%' AND so.status='Fulfilled';
UPDATE stocks s JOIN sales_orders so ON so.warehouse_id=s.warehouse_id JOIN sales_order_details d ON d.sales_order_id=so.id AND d.product_id=s.product_id
SET s.stock_out=s.stock_out+d.qty,s.current_stock=s.current_stock-d.qty WHERE so.so_number LIKE 'SO-SEED-%' AND so.status='Fulfilled';
