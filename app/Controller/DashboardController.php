<?php
namespace App\Controller;
use App\Repository\DashboardRepository;
use App\Repository\InventoryRepository;
use App\Security\Auth;
final class DashboardController extends BaseController {
 public function index(): void {
  $isAdmin=in_array(Auth::role(),['Admin','WarehouseStaff'],true);$member=$isAdmin?null:Auth::id();$r=new DashboardRepository($this->pdo);$inv=new InventoryRepository($this->pdo);
  $this->view('dashboard/index',['pageTitle'=>'Dashboard','summary'=>$r->summary($member),'nearest'=>$r->nearest($member),'isAdmin'=>$isAdmin,'inventory'=>$isAdmin?$inv->summary():[],'lowStock'=>$isAdmin?$inv->lowStock():[]]);
 }
}
