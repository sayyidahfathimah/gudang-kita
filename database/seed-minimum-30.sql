DELIMITER //
CREATE PROCEDURE seed_minimum_30()
BEGIN
  DECLARE n INT DEFAULT 1;
  DECLARE product_id INT; DECLARE warehouse_id INT; DECLARE project_id INT; DECLARE user_id INT; DECLARE order_id INT; DECLARE idx INT;
  INSERT IGNORE INTO users(username,password,name,email,role,is_active) VALUES ('member1','$2y$12$yfIZUXvwqO6zDrTBzT8fNOqCpRt1TaXi9sU.mxi4YHNeHNihU4kB.','Member One','member1@example.test','Member',1);
  WHILE (SELECT COUNT(*) FROM users) < 30 DO
    INSERT INTO users(username,password,name,email,role,is_active) VALUES (CONCAT('member',LPAD(n,2,'0')),'$2y$12$yfIZUXvwqO6zDrTBzT8fNOqCpRt1TaXi9sU.mxi4YHNeHNihU4kB.',CONCAT('Member ',n),CONCAT('member',n,'@example.test'),'Member',1); SET n=n+1;
  END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM products) < 30 DO INSERT INTO products(code,name,category,unit,purchase_price,selling_price,minimum_stock,status) VALUES (CONCAT('PRD',LPAD(20+n,3,'0')),CONCAT('Produk Demo ',n),'Demo','PCS',10000+n*1000,15000+n*1000,5,'Active'); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM customers) < 30 DO INSERT INTO customers(code,name,address,phone,email,status) VALUES (CONCAT('CUS',LPAD(11+n,3,'0')),CONCAT('Customer Demo ',n),'Jakarta',CONCAT('0812',LPAD(n,8,'0')),CONCAT('customer',n,'@example.test'),'Active'); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM suppliers) < 30 DO INSERT INTO suppliers(code,name,address,phone,email,status) VALUES (CONCAT('SUP',LPAD(10+n,3,'0')),CONCAT('Supplier Demo ',n),'Jakarta',CONCAT('0813',LPAD(n,8,'0')),CONCAT('supplier',n,'@example.test'),'Active'); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM warehouses) < 30 DO INSERT INTO warehouses(code,name,address,phone,status) VALUES (CONCAT('WH',LPAD(10+n,3,'0')),CONCAT('Gudang Demo ',n),'Jakarta',CONCAT('021',LPAD(n,7,'0')),'Active'); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM projects) < 30 DO INSERT INTO projects(name,description,status,start_date,target_date) VALUES (CONCAT('Project Demo ',n),'Project untuk data demo DevOps.',IF(n%3=0,'Completed','Active'),'2026-09-01','2026-12-31'); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM tasks) < 30 DO SET idx=(n-1)%30; SELECT id INTO project_id FROM projects ORDER BY id LIMIT idx,1; SET idx=(n-1)%29; SELECT id INTO user_id FROM users WHERE role='Member' ORDER BY id LIMIT idx,1; INSERT INTO tasks(project_id,title,description,assignee_id,status,priority,due_date) VALUES (project_id,CONCAT('Task Demo ',n),'Task untuk data demo DevOps.',user_id,IF(n%3=0,'Done',IF(n%3=1,'To Do','In Progress')),IF(n%3=0,'Low',IF(n%3=1,'Medium','High')),'2026-12-31'); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM purchase_orders) < 30 DO INSERT INTO purchase_orders(po_number,supplier_id,warehouse_id,po_date,status,notes,total_amount) VALUES (CONCAT('PO-DEV-',LPAD(n,3,'0')),1,1,'2026-09-29','Received','Data demo',100000); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM purchase_order_details) < 30 DO SELECT id INTO order_id FROM purchase_orders ORDER BY id DESC LIMIT 1; INSERT INTO purchase_order_details(purchase_order_id,product_id,qty,price,subtotal) VALUES (order_id,1,1,100000,100000); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM sales_orders) < 30 DO INSERT INTO sales_orders(so_number,customer_id,warehouse_id,so_date,status,notes,total_amount) VALUES (CONCAT('SO-DEV-',LPAD(n,3,'0')),1,1,'2026-09-29','Draft','Data demo',120000); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM sales_order_details) < 30 DO SELECT id INTO order_id FROM sales_orders ORDER BY id DESC LIMIT 1; INSERT INTO sales_order_details(sales_order_id,product_id,qty,price,subtotal) VALUES (order_id,1,1,120000,120000); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM stocks) < 30 DO SET idx=(n-1)%30; SELECT id INTO product_id FROM products ORDER BY id LIMIT idx,1; SELECT id INTO warehouse_id FROM warehouses ORDER BY id LIMIT idx,1; INSERT IGNORE INTO stocks(product_id,warehouse_id,stock_in,stock_out,current_stock,updated_at) VALUES (product_id,warehouse_id,100,10,90,NOW()); SET n=n+1; END WHILE;
  SET n=1; WHILE (SELECT COUNT(*) FROM stock_movements) < 30 DO INSERT INTO stock_movements(transaction_type,transaction_id,transaction_number,product_id,warehouse_id,qty,movement_date) VALUES ('IN',1,CONCAT('SEED-',LPAD(n,3,'0')),1,1,10,CURDATE()); SET n=n+1; END WHILE;
  UPDATE tasks SET due_date=CASE id WHEN 1 THEN '2026-09-15' WHEN 2 THEN '2026-09-20' WHEN 3 THEN '2026-09-25' END, status=CASE id WHEN 1 THEN 'To Do' WHEN 2 THEN 'In Progress' WHEN 3 THEN 'To Do' END WHERE id IN (1,2,3);
  UPDATE tasks SET assignee_id=(SELECT id FROM users WHERE username='member1') WHERE id IN (1,2,3);
END//
DELIMITER ;
CALL seed_minimum_30();
DROP PROCEDURE seed_minimum_30;
