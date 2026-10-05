USE inventory_db;

UPDATE users target
JOIN (
    SELECT email, MIN(id) AS retained_id
    FROM users
    GROUP BY email
    HAVING COUNT(*) > 1
) duplicate ON duplicate.email = target.email AND target.id <> duplicate.retained_id
SET target.email = CONCAT(target.username, '+', target.id, '@gudangkita.local');

ALTER TABLE users ADD CONSTRAINT uq_users_email UNIQUE (email);
