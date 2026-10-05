<?php

namespace App\Repository;

use App\Security\Hash;

final class UserRepository
{
    public function __construct(private \PDO $pdo)
    {
    }
    public function findByEmail(string $email): ?array
    {
        $s = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }
    public function find(int $id): ?array
    {
        $s = $this->pdo->prepare('SELECT id,username,name,email,role,is_active,created_at FROM users WHERE id=?');
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }
    public function all(): array
    {
        return $this->pdo->query('SELECT id,username,name,email,role,is_active,created_at FROM users ORDER BY name')->fetchAll();
    }
    public function identityErrors(array $data, int $exceptId = 0): array
    {
        $errors = [];
        foreach (['username' => 'Username', 'email' => 'Email'] as $field => $label) {
            $statement = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE {$field} = ? AND id <> ?");
            $statement->execute([$data[$field], $exceptId]);
            if ((int) $statement->fetchColumn() > 0) {
                $errors[$field] = $label . ' sudah digunakan.';
            }
        }
        return $errors;
    }
    public function create(array $d): int
    {
        $s = $this->pdo->prepare('INSERT INTO users(username,password,name,email,role,is_active) VALUES(?,?,?,?,?,?)');
        $s->execute([$d['username'],Hash::make($d['password']),$d['name'],$d['email'] ?: null,$d['role'],(int)$d['is_active']]);
        return (int)$this->pdo->lastInsertId();
    }
    public function update(int $id, array $d): void
    {
        $s = $this->pdo->prepare('UPDATE users SET username=?,name=?,email=?,role=?,is_active=? WHERE id=?');
        $s->execute([$d['username'],$d['name'],$d['email'] ?: null,$d['role'],(int)$d['is_active'],$id]);
        if (!empty($d['password'])) {
            $s = $this->pdo->prepare('UPDATE users SET password=? WHERE id=?');
            $s->execute([Hash::make($d['password']),$id]);
        }
    }
    /**
     * Permanently remove unreferenced users. Keep users assigned to tasks as
     * inactive so the task history retains its original assignee.
     */
    public function delete(int $id): bool
    {
        $s = $this->pdo->prepare('SELECT id FROM users WHERE id=? FOR UPDATE');
        $s->execute([$id]);
        if (!$s->fetch()) {
            return false;
        }

        $s = $this->pdo->prepare('SELECT COUNT(*) FROM tasks WHERE assignee_id=?');
        $s->execute([$id]);
        if ((int) $s->fetchColumn() > 0) {
            $s = $this->pdo->prepare('UPDATE users SET is_active=0 WHERE id=?');
            $s->execute([$id]);
            return true;
        }

        try {
            $s = $this->pdo->prepare('DELETE FROM users WHERE id=?');
            $s->execute([$id]);
            return false;
        } catch (\PDOException $exception) {
            // A related record may have been created between the check and
            // delete. Preserve it and deactivate the account instead.
            if ((string) $exception->getCode() !== '23000') {
                throw $exception;
            }
            $s = $this->pdo->prepare('UPDATE users SET is_active=0 WHERE id=?');
            $s->execute([$id]);
            return true;
        }
    }
}
