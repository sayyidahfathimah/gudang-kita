USE inventory_db;

ALTER TABLE users MODIFY role ENUM('Admin','Member','Sales','WarehouseStaff') NOT NULL DEFAULT 'Sales';
UPDATE users SET role = 'Sales' WHERE role = 'Member';
ALTER TABLE users MODIFY role ENUM('Admin','Sales','WarehouseStaff') NOT NULL DEFAULT 'Sales';

UPDATE sales_orders SET status = CASE status WHEN 'Confirmed' THEN 'Approved' WHEN 'Completed' THEN 'Fulfilled' ELSE status END;
ALTER TABLE sales_orders
    ADD COLUMN created_by INT NULL AFTER customer_id,
    ADD COLUMN approved_by INT NULL AFTER created_by,
    MODIFY status ENUM('Draft','PendingApproval','Approved','Fulfilled','Cancelled') NOT NULL DEFAULT 'Draft';
ALTER TABLE sales_orders ADD INDEX idx_so_created_by(created_by), ADD INDEX idx_so_approved_by(approved_by);
ALTER TABLE sales_orders ADD CONSTRAINT fk_so_created_by FOREIGN KEY(created_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;
ALTER TABLE sales_orders ADD CONSTRAINT fk_so_approved_by FOREIGN KEY(approved_by) REFERENCES users(id) ON UPDATE CASCADE ON DELETE SET NULL;

UPDATE purchase_orders SET status = CASE status WHEN 'Approved' THEN 'Ordered' ELSE status END;
ALTER TABLE purchase_orders MODIFY status ENUM('Draft','Ordered','PartiallyReceived','Received','Cancelled') NOT NULL DEFAULT 'Draft';

ALTER TABLE purchase_order_details ADD COLUMN received_qty DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER qty;
