USE inventory_db;

UPDATE users
SET email = CONCAT(username, '@gudangkita.local')
WHERE email IS NULL OR TRIM(email) = '';

ALTER TABLE users MODIFY email VARCHAR(150) NOT NULL;
