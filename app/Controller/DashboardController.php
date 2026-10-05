<?php

namespace App\Controller;

use App\Repository\DashboardRepository;
use App\Repository\InventoryRepository;
use App\Security\Auth;

final class DashboardController extends BaseController
{
    public function index(): void
    {
        $role = Auth::role();
        $isAdmin = $role === 'Admin';
        $isWarehouse = $role === 'WarehouseStaff';
        $member = $role === 'Sales' ? Auth::id() : null;
        $r = new DashboardRepository($this->pdo);
        $inv = new InventoryRepository($this->pdo);
        $this->view('dashboard/index', [
            'pageTitle' => 'Dashboard',
            'role' => $role,
            'summary' => $isWarehouse ? [] : $r->summary($member),
            'nearest' => $isWarehouse ? [] : $r->nearest($member),
            'isAdmin' => $isAdmin,
            'isWarehouse' => $isWarehouse,
            'inventory' => ($isAdmin || $isWarehouse) ? $inv->summary() : [],
            'lowStock' => ($isAdmin || $isWarehouse) ? $inv->lowStock() : [],
            'salesOrders' => $role === 'Sales' ? $r->salesOrderCounts(Auth::id()) : [],
            'orders' => $isAdmin ? $r->orderCounts() : [],
            'queues' => $isWarehouse ? $r->warehouseQueues() : [],
        ]);
    }
}
