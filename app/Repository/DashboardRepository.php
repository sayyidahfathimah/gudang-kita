<?php

namespace App\Repository;

final class DashboardRepository
{
    public function __construct(private \PDO $pdo)
    {
    }
    public function summary(?int $memberId = null): array
    {
        $cond = $memberId !== null ? ' AND assignee_id=?' : '';
        $p = $memberId !== null ? [$memberId] : [];
        $q = function (string $sql) use ($p) {
            $s = $this->pdo->prepare($sql);
            $s->execute($p);
            return (int)$s->fetchColumn();
        };
        return ['active_projects' => $memberId === null ? (int)$this->pdo->query("SELECT COUNT(*) FROM projects WHERE status='Active'")->fetchColumn() : $q("SELECT COUNT(DISTINCT project_id) FROM tasks WHERE assignee_id=? AND status<>'Done'"),'todo' => $q("SELECT COUNT(*) FROM tasks WHERE status='To Do'{$cond}"),'progress' => $q("SELECT COUNT(*) FROM tasks WHERE status='In Progress'{$cond}"),'done' => $q("SELECT COUNT(*) FROM tasks WHERE status='Done'{$cond}"),'overdue' => $q("SELECT COUNT(*) FROM tasks WHERE due_date<CURDATE() AND status<>'Done'{$cond}")];
    }
    public function nearest(?int $memberId = null): array
    {
        $where = $memberId !== null ? ' WHERE t.assignee_id=? AND t.status<>\'Done\'' : ' WHERE t.status<>\'Done\'';
        $s = $this->pdo->prepare("SELECT t.id,t.title,t.due_date,t.status,t.priority,p.name project_name,u.name assignee_name FROM tasks t JOIN projects p ON p.id=t.project_id JOIN users u ON u.id=t.assignee_id{$where}
        ORDER BY t.due_date ASC LIMIT 5");
        $s->execute($memberId !== null ? [$memberId] : []);
        return $s->fetchAll();
    }
}
