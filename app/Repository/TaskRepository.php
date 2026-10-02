<?php
namespace App\Repository;
final class TaskRepository {
 public function __construct(private \PDO $pdo) {}
 public function find(int $id): ?array { $s=$this->pdo->prepare('SELECT t.*,p.name project_name,p.start_date project_start,p.target_date project_target,u.name assignee_name FROM tasks t JOIN projects p ON p.id=t.project_id JOIN users u ON u.id=t.assignee_id WHERE t.id=?');$s->execute([$id]);return $s->fetch()?:null; }
 public function list(array $f=[],?int $memberId=null): array {
  $where=[];$p=[]; if($memberId!==null){$where[]='t.assignee_id=?';$p[]=$memberId;} if(($f['search']??'')!==''){$where[]='t.title LIKE ?';$p[]='%'.$f['search'].'%';} if(($f['project_id']??'')!==''){$where[]='t.project_id=?';$p[]=(int)$f['project_id'];} if(($f['status']??'')!==''){$where[]='t.status=?';$p[]=$f['status'];} if(($f['priority']??'')!==''){$where[]='t.priority=?';$p[]=$f['priority'];}
  $allowed=['due_asc'=>'t.due_date ASC','due_desc'=>'t.due_date DESC'];$sort=$allowed[$f['sort']??'due_asc']??$allowed['due_asc'];$base=' FROM tasks t JOIN projects p ON p.id=t.project_id JOIN users u ON u.id=t.assignee_id';$whereSql=$where?' WHERE '.implode(' AND ',$where):'';
  $c=$this->pdo->prepare('SELECT COUNT(*)'.$base.$whereSql);$c->execute($p);$total=(int)$c->fetchColumn();$page=max(1,(int)($f['page']??1));$per=10;$offset=($page-1)*$per;
  $s=$this->pdo->prepare('SELECT t.*,p.name project_name,u.name assignee_name'.$base.$whereSql." ORDER BY {$sort} LIMIT {$per} OFFSET {$offset}");$s->execute($p);return ['items'=>$s->fetchAll(),'total'=>$total,'page'=>$page,'per_page'=>$per,'pages'=>max(1,(int)ceil($total/$per))];
 }
 public function create(array $d): int { $s=$this->pdo->prepare('INSERT INTO tasks(project_id,title,description,assignee_id,status,priority,due_date) VALUES(?,?,?,?,?,?,?)');$s->execute([$d['project_id'],$d['title'],$d['description'],$d['assignee_id'],$d['status'],$d['priority'],$d['due_date']]);return (int)$this->pdo->lastInsertId(); }
 public function update(int $id,array $d): void { $s=$this->pdo->prepare('UPDATE tasks SET project_id=?,title=?,description=?,assignee_id=?,status=?,priority=?,due_date=? WHERE id=?');$s->execute([$d['project_id'],$d['title'],$d['description'],$d['assignee_id'],$d['status'],$d['priority'],$d['due_date'],$id]); }
 public function updateStatus(int $id,int $memberId,string $status): bool { $s=$this->pdo->prepare('UPDATE tasks SET status=? WHERE id=? AND assignee_id=?');$s->execute([$status,$id,$memberId]);return $s->rowCount()===1; }
 public function delete(int $id): void { $s=$this->pdo->prepare('DELETE FROM tasks WHERE id=?');$s->execute([$id]); }
}
