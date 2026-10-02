<?php
namespace App\Repository;
use App\Security\Hash;
final class UserRepository {
 public function __construct(private \PDO $pdo) {}
 public function findByUsername(string $username): ?array { $s=$this->pdo->prepare('SELECT * FROM users WHERE username=? LIMIT 1'); $s->execute([$username]); return $s->fetch()?:null; }
 public function find(int $id): ?array { $s=$this->pdo->prepare('SELECT id,username,name,email,role,is_active,created_at FROM users WHERE id=?'); $s->execute([$id]); return $s->fetch()?:null; }
 public function all(): array { return $this->pdo->query('SELECT id,username,name,email,role,is_active,created_at FROM users ORDER BY name')->fetchAll(); }
 public function create(array $d): int { $s=$this->pdo->prepare('INSERT INTO users(username,password,name,email,role,is_active) VALUES(?,?,?,?,?,?)'); $s->execute([$d['username'],Hash::make($d['password']),$d['name'],$d['email']?:null,$d['role'],(int)$d['is_active']]); return (int)$this->pdo->lastInsertId(); }
 public function update(int $id,array $d): void { $s=$this->pdo->prepare('UPDATE users SET username=?,name=?,email=?,role=?,is_active=? WHERE id=?'); $s->execute([$d['username'],$d['name'],$d['email']?:null,$d['role'],(int)$d['is_active'],$id]); if (!empty($d['password'])) { $s=$this->pdo->prepare('UPDATE users SET password=? WHERE id=?'); $s->execute([Hash::make($d['password']),$id]); } }
 public function delete(int $id): void { $s=$this->pdo->prepare('DELETE FROM users WHERE id=?'); $s->execute([$id]); }
}
