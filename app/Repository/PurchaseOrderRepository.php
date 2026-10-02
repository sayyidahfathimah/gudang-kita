<?php
namespace App\Repository;
final class PurchaseOrderRepository {
 public function __construct(private \PDO $pdo) {}
 public function all():array {
  try {
    $sql="SELECT po.*,s.name AS supplier_name,w.name AS warehouse_name
          FROM purchase_orders po
          LEFT JOIN suppliers s ON s.id=po.supplier_id
          LEFT JOIN warehouses w ON w.id=po.warehouse_id
          ORDER BY po.id DESC";
    return $this->pdo->query($sql)->fetchAll();
  } catch (\Throwable $e) {
    error_log('PurchaseOrderRepository::all: '.$e->getMessage());
    try {
      $rows=$this->pdo->query("SELECT po.* FROM purchase_orders po ORDER BY po.id DESC")->fetchAll();
      foreach($rows as &$row){
        $row['supplier_name']=$row['supplier_name']??'-';
        $row['warehouse_name']=$row['warehouse_name']??'-';
      }
      unset($row);
      return $rows;
    } catch (\Throwable $fallback) {
      error_log('PurchaseOrderRepository::all fallback: '.$fallback->getMessage());
      return [];
    }
  }
}
 public function find(int $id):?array{$s=$this->pdo->prepare("SELECT po.*,s.name supplier_name,w.name warehouse_name FROM purchase_orders po JOIN suppliers s ON s.id=po.supplier_id JOIN warehouses w ON w.id=po.warehouse_id WHERE po.id=?");$s->execute([$id]);$r=$s->fetch();if(!$r)return null;$q=$this->pdo->prepare("SELECT d.*,p.code product_code,p.name product_name,p.unit FROM purchase_order_details d JOIN products p ON p.id=d.product_id WHERE d.purchase_order_id=? ORDER BY d.id");$q->execute([$id]);$r['details']=$q->fetchAll();return $r;}
 public function create(array $d,array $details):int{$this->pdo->beginTransaction();try{$n='PO-'.date('Ymd-His');$s=$this->pdo->prepare('INSERT INTO purchase_orders(po_number,supplier_id,warehouse_id,po_date,status,notes,total_amount) VALUES(?,?,?,?,?,?,0)');$s->execute([$n,$d['supplier_id'],$d['warehouse_id'],$d['po_date'],$d['status'],$d['notes']]);$id=(int)$this->pdo->lastInsertId();$total=0;foreach($details as $x){$sub=$x['qty']*$x['price'];$this->pdo->prepare('INSERT INTO purchase_order_details(purchase_order_id,product_id,qty,price,subtotal) VALUES(?,?,?,?,?)')->execute([$id,$x['product_id'],$x['qty'],$x['price'],$sub]);$total+=$sub;} $this->pdo->prepare('UPDATE purchase_orders SET total_amount=? WHERE id=?')->execute([$total,$id]);if($d['status']==='Received')$this->applyStock($id,'IN',$n);$this->pdo->commit();return $id;}catch(\Throwable $e){$this->pdo->rollBack();throw $e;}}
 public function setStatus(int $id,string $status):void{$r=$this->find($id);if(!$r)throw new \RuntimeException('PO tidak ditemukan');if($r['status']==='Received' && $status!=='Received')throw new \RuntimeException('PO yang sudah diterima tidak dapat dibatalkan.');$this->pdo->beginTransaction();try{$this->pdo->prepare('UPDATE purchase_orders SET status=? WHERE id=?')->execute([$status,$id]);if($status==='Received'&&$r['status']!=='Received')$this->applyStock($id,'IN',$r['po_number']);$this->pdo->commit();}catch(\Throwable $e){$this->pdo->rollBack();throw $e;}}
 private function applyStock(int $id,string $type,string $number):void{$q=$this->pdo->prepare('SELECT * FROM purchase_order_details WHERE purchase_order_id=?');$q->execute([$id]);foreach($q->fetchAll() as $d){$this->pdo->prepare('INSERT INTO stocks(product_id,warehouse_id,stock_in,stock_out,current_stock,updated_at) VALUES(?,?,?,0,?,NOW()) ON DUPLICATE KEY UPDATE stock_in=stock_in+VALUES(stock_in),current_stock=current_stock+VALUES(current_stock),updated_at=NOW()')->execute([$d['product_id'],$this->find($id)['warehouse_id'],$d['qty'],$d['qty']]);$this->pdo->prepare('INSERT INTO stock_movements(transaction_type,transaction_id,transaction_number,product_id,warehouse_id,qty,movement_date) VALUES(?,?,?,?,?,?,NOW())')->execute([$type,$id,$number,$d['product_id'],$this->find($id)['warehouse_id'],$d['qty']]);}}
}
