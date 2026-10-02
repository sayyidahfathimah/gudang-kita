<?php
namespace App\Controller;
use App\Repository\InventoryRepository;
use App\Security\Auth;
final class InventoryController extends BaseController {
 private InventoryRepository $repo;public function __construct(\PDO $pdo){parent::__construct($pdo);$this->repo=new InventoryRepository($pdo);}
 public function stock():void{Auth::require();$f=['search'=>trim((string)($_GET['search']??'')),'warehouse_id'=>(string)($_GET['warehouse_id']??'')];$result=paginate($this->repo->stockRows($f),(int)($_GET['current_page']??1));$this->view('inventory/stock',['pageTitle'=>'Stok','result'=>$result,'warehouses'=>$this->repo->warehouses(),'filters'=>$f]);}
 public function movements():void{Auth::require();$f=['product_id'=>(string)($_GET['product_id']??''),'warehouse_id'=>(string)($_GET['warehouse_id']??'')];$result=paginate($this->repo->movements($f),(int)($_GET['current_page']??1));$this->view('inventory/movements',['pageTitle'=>'Stock Movement','result'=>$result,'products'=>$this->repo->products(),'warehouses'=>$this->repo->warehouses(),'filters'=>$f]);}
}
