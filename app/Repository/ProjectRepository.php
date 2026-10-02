<?php
namespace App\Repository;
final class ProjectRepository {
 public function __construct(private \PDO $pdo) {}
 public function find(int $id): ?array { $s=$this->pdo->prepare('SELECT * FROM projects WHERE id=?'); $s->execute([$id]); return $s->fetch()?:null; }
 public function all(array $filters=[]): array { $where=[];$p=[]; if(($filters['search']??'')!==''){ $where[]='p.name LIKE ?';$p[]='%'.$filters['search'].'%'; } if(($filters['status']??'')!==''){ $where[]='p.status=?';$p[]=$filters['status']; } $sql='SELECT p.*, (SELECT COUNT(*) FROM tasks t WHERE t.project_id=p.id) task_count FROM projects p'; if($where)$sql.=' WHERE '.implode(' AND ',$where); $sql.=' ORDER BY p.start_date DESC'; $s=$this->pdo->prepare($sql);$s->execute($p);return $s->fetchAll(); }
 public function forMember(int $userId,array $filters=[]): array { $where=['t.assignee_id=?'];$p=[$userId]; if(($filters['search']??'')!==''){ $where[]='p.name LIKE ?';$p[]='%'.$filters['search'].'%'; } if(($filters['status']??'')!==''){ $where[]='p.status=?';$p[]=$filters['status']; } $sql='SELECT DISTINCT p.*, (SELECT COUNT(*) FROM tasks x WHERE x.project_id=p.id) task_count FROM projects p JOIN tasks t ON t.project_id=p.id WHERE '.implode(' AND ',$where).' ORDER BY p.start_date DESC';$s=$this->pdo->prepare($sql);$s->execute($p);return $s->fetchAll(); }
 public function create(array $d): int { $s=$this->pdo->prepare('INSERT INTO projects(name,description,status,start_date,target_date) VALUES(?,?,?,?,?)');$s->execute([$d['name'],$d['description'],$d['status'],$d['start_date'],$d['target_date']]);return (int)$this->pdo->lastInsertId(); }
 public function update(int $id,array $d): void { $s=$this->pdo->prepare('UPDATE projects SET name=?,description=?,status=?,start_date=?,target_date=? WHERE id=?');$s->execute([$d['name'],$d['description'],$d['status'],$d['start_date'],$d['target_date'],$id]); }
 public function archive(int $id): void { $s=$this->pdo->prepare("UPDATE projects SET status='Archived' WHERE id=?");$s->execute([$id]); }
 public function taskCount(int $id): int { $s=$this->pdo->prepare('SELECT COUNT(*) FROM tasks WHERE project_id=?');$s->execute([$id]);return (int)$s->fetchColumn(); }
}
