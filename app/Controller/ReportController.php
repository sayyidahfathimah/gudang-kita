<?php

namespace App\Controller;

use App\Security\Auth;

final class ReportController extends BaseController
{
    public function index(): void
    {
        Auth::require();
        $stock = $this->pdo->query("SELECT p.code,p.name,w.name warehouse_name,s.current_stock,p.minimum_stock FROM stocks s JOIN products p ON p.id=s.product_id JOIN warehouses w ON w.id=s.warehouse_id ORDER BY p.name,w.name")->fetchAll();
        $purchase = $this->pdo->query("SELECT DATE_FORMAT(po_date,'%Y-%m') period,COUNT(*) orders,COALESCE(SUM(total_amount),0) total FROM purchase_orders WHERE status='Received' GROUP BY DATE_FORMAT(po_date,'%Y-%m') ORDER BY period DESC LIMIT 12")->fetchAll();
        $sales = $this->pdo->query("SELECT DATE_FORMAT(so_date,'%Y-%m') period,COUNT(*) orders,COALESCE(SUM(total_amount),0) total FROM sales_orders WHERE status='Completed' GROUP BY DATE_FORMAT(so_date,'%Y-%m') ORDER BY period DESC LIMIT 12")->fetchAll();
        $this->view('reports/index', ['pageTitle' => 'Laporan','stock' => $stock,'purchase' => $purchase,'sales' => $sales]);
    }
}
