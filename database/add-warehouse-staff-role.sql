USE inventory_db;
ALTER TABLE users MODIFY role ENUM('Admin','WarehouseStaff','Member') NOT NULL DEFAULT 'Member';
INSERT IGNORE INTO users(username,password,name,email,role,is_active) VALUES
('warehouse1','$2y$12$yfIZUXvwqO6zDrTBzT8fNOqCpRt1TaXi9sU.mxi4YHNeHNihU4kB.','Petugas Gudang','warehouse1@example.test','WarehouseStaff',1);
