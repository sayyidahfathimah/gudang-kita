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

    public function salesOrderCounts(int $salesId): array
    {
        $statement = $this->pdo->prepare('SELECT status,COUNT(*) total FROM sales_orders WHERE created_by=? GROUP BY status');
        $statement->execute([$salesId]);
        return $this->statusMap($statement->fetchAll(), ['Draft','PendingApproval','Approved','Fulfilled','Cancelled']);
    }

    public function orderCounts(): array
    {
        $po = $this->pdo->query('SELECT status,COUNT(*) total FROM purchase_orders GROUP BY status')->fetchAll();
        $so = $this->pdo->query('SELECT status,COUNT(*) total FROM sales_orders GROUP BY status')->fetchAll();
        return [
            'purchase' => $this->statusMap($po, ['Draft','Ordered','PartiallyReceived','Received','Cancelled']),
            'sales' => $this->statusMap($so, ['Draft','PendingApproval','Approved','Fulfilled','Cancelled']),
        ];
    }

    public function warehouseQueues(): array
    {
        $purchase = $this->pdo->query("SELECT po.id,po.po_number,po.status,po.po_date,s.name supplier_name,w.name warehouse_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id JOIN warehouses w ON w.id=po.warehouse_id WHERE po.status IN ('Ordered','PartiallyReceived') ORDER BY po.po_date ASC LIMIT 10")->fetchAll();
        $sales = $this->pdo->query("SELECT so.id,so.so_number,so.status,so.so_date,c.name customer_name,w.name warehouse_name FROM sales_orders so JOIN customers c ON c.id=so.customer_id JOIN warehouses w ON w.id=so.warehouse_id WHERE so.status='Approved' ORDER BY so.so_date ASC LIMIT 10")->fetchAll();
        return ['receipts' => $purchase, 'issues' => $sales];
    }

    private function statusMap(array $rows, array $statuses): array
    {
        $counts = array_fill_keys($statuses, 0);
        foreach ($rows as $row) {
            if (array_key_exists($row['status'], $counts)) {
                $counts[$row['status']] = (int) $row['total'];
            }
        }
        return $counts;
    }
}
