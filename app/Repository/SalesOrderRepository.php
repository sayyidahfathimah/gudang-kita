<?php
namespace App\Repository;
final class SalesOrderRepository {
 public function __construct(private \PDO $pdo) {}
 public function all():array {
  try {
    $sql="SELECT so.*,c.name AS customer_name,w.name AS warehouse_name
          FROM sales_orders so
          LEFT JOIN customers c ON c.id=so.customer_id
          LEFT JOIN warehouses w ON w.id=so.warehouse_id
          ORDER BY so.id DESC";
    return $this->pdo->query($sql)->fetchAll();
  } catch (\Throwable $e) {
    error_log('SalesOrderRepository::all: '.$e->getMessage());
    try {
      $rows=$this->pdo->query("SELECT so.* FROM sales_orders so ORDER BY so.id DESC")->fetchAll();
      foreach($rows as &$row){
        $row['customer_name']=$row['customer_name']??'-';
        $row['warehouse_name']=$row['warehouse_name']??'-';
      }
      unset($row);
      return $rows;
    } catch (\Throwable $fallback) {
      error_log('SalesOrderRepository::all fallback: '.$fallback->getMessage());
      return [];
    }
  }
}
 public function find(int $id):?array{$s=$this->pdo->prepare("SELECT so.*,c.name customer_name,w.name warehouse_name FROM sales_orders so JOIN customers c ON c.id=so.customer_id JOIN warehouses w ON w.id=so.warehouse_id WHERE so.id=?");$s->execute([$id]);$r=$s->fetch();if(!$r)return null;$q=$this->pdo->prepare("SELECT d.*,p.code product_code,p.name product_name,p.unit FROM sales_order_details d JOIN products p ON p.id=d.product_id WHERE d.sales_order_id=? ORDER BY d.id");$q->execute([$id]);$r['details']=$q->fetchAll();return $r;}
 public function create(array $d,array $details):int{$this->pdo->beginTransaction();try{$n='SO-'.date('Ymd-His');$s=$this->pdo->prepare('INSERT INTO sales_orders(so_number,customer_id,warehouse_id,so_date,status,notes,total_amount) VALUES(?,?,?,?,?,?,0)');$s->execute([$n,$d['customer_id'],$d['warehouse_id'],$d['so_date'],$d['status'],$d['notes']]);$id=(int)$this->pdo->lastInsertId();$total=0;foreach($details as $x){$sub=$x['qty']*$x['price'];$this->pdo->prepare('INSERT INTO sales_order_details(sales_order_id,product_id,qty,price,subtotal) VALUES(?,?,?,?,?)')->execute([$id,$x['product_id'],$x['qty'],$x['price'],$sub]);$total+=$sub;} $this->pdo->prepare('UPDATE sales_orders SET total_amount=? WHERE id=?')->execute([$total,$id]);if($d['status']==='Completed'){$this->ensureEnough(['id'=>$id,'warehouse_id'=>$d['warehouse_id']]);$this->applyStock($id,'OUT',$n);}$this->pdo->commit();return $id;}catch(\Throwable $e){$this->pdo->rollBack();throw $e;}}
 public function setStatus(int $id,string $status):void{$r=$this->find($id);if(!$r)throw new \RuntimeException('SO tidak ditemukan');if($r['status']==='Completed' && $status!=='Completed')throw new \RuntimeException('SO yang sudah selesai tidak dapat dibatalkan.');$this->pdo->beginTransaction();try{if($status==='Completed'&&$r['status']!=='Completed'){$this->ensureEnough($r);$this->applyStock($id,'OUT',$r['so_number']);}$this->pdo->prepare('UPDATE sales_orders SET status=? WHERE id=?')->execute([$status,$id]);$this->pdo->commit();}catch(\Throwable $e){$this->pdo->rollBack();throw $e;}}
 private function ensureEnough(array $r):void{$q=$this->pdo->prepare('SELECT d.product_id,p.name,SUM(d.qty) qty,COALESCE(s.current_stock,0) current_stock FROM sales_order_details d JOIN products p ON p.id=d.product_id LEFT JOIN stocks s ON s.product_id=d.product_id AND s.warehouse_id=? WHERE d.sales_order_id=? GROUP BY d.product_id,p.name,s.current_stock');$q->execute([$r['warehouse_id'],$r['id']]);foreach($q->fetchAll() as $x){if((float)$x['current_stock']<(float)$x['qty'])throw new \RuntimeException('Stok '. $x['name'] .' tidak cukup. Stok tersedia: '.rtrim(rtrim(number_format((float)$x['current_stock'],2,'.',''),'0'),'.').'.');}}
 private function applyStock(int $id,string $type,string $number):void{$r=$this->find($id);$q=$this->pdo->prepare('SELECT * FROM sales_order_details WHERE sales_order_id=?');$q->execute([$id]);foreach($q->fetchAll() as $d){$this->pdo->prepare('INSERT INTO stocks(product_id,warehouse_id,stock_in,stock_out,current_stock,updated_at) VALUES(?,?,0,?, -?,NOW()) ON DUPLICATE KEY UPDATE stock_out=stock_out+VALUES(stock_out),current_stock=current_stock-VALUES(stock_out),updated_at=NOW()')->execute([$d['product_id'],$r['warehouse_id'],$d['qty'],$d['qty']]);$this->pdo->prepare('INSERT INTO stock_movements(transaction_type,transaction_id,transaction_number,product_id,warehouse_id,qty,movement_date) VALUES(?,?,?,?,?,?,NOW())')->execute([$type,$id,$number,$d['product_id'],$r['warehouse_id'],$d['qty']]);}}
}
