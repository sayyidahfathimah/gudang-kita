<?php
namespace App\Repository;
final class InventoryMasterRepository {
    public function __construct(private \PDO $pdo) {}
    private array $cfg = [
        'products'=>['table'=>'products','title'=>'Produk','singular'=>'Produk','fields'=>['code','name','category','unit','purchase_price','selling_price','minimum_stock','status'],'search'=>['code','name','category']],
        'customers'=>['table'=>'customers','title'=>'Customer','singular'=>'Customer','fields'=>['code','name','address','phone','email','status'],'search'=>['code','name','email']],
        'suppliers'=>['table'=>'suppliers','title'=>'Supplier','singular'=>'Supplier','fields'=>['code','name','address','phone','email','status'],'search'=>['code','name','email']],
        'warehouses'=>['table'=>'warehouses','title'=>'Gudang','singular'=>'Gudang','fields'=>['code','name','address','phone','status'],'search'=>['code','name']],
    ];
    public function config(string $type): array { return $this->cfg[$type] ?? throw new \InvalidArgumentException('Master tidak valid.'); }
    public function all(string $type, array $f=[]): array {
        $c=$this->config($type); $where=[]; $p=[]; $search=trim((string)($f['search']??''));
        if($search!==''){ $where[]='('.implode(' LIKE ? OR ',array_map(fn($x)=>'m.'.$x,$c['search'])).' LIKE ?)'; foreach($c['search'] as $x)$p[]='%'.$search.'%'; }
        $status=(string)($f['status']??''); if($status!==''){ $where[]='m.status=?';$p[]=$status; }
        $sql='SELECT m.* FROM '.$c['table'].' m'.($where?' WHERE '.implode(' AND ',$where):'').' ORDER BY m.id DESC';
        $s=$this->pdo->prepare($sql);$s->execute($p);return $s->fetchAll();
    }
    public function paginate(string $type, array $f=[], int $page=1, int $perPage=10): array {
        $c=$this->config($type);$where=[];$params=[];$search=trim((string)($f['search']??''));
        if($search!==''){ $where[]='('.implode(' LIKE ? OR ',array_map(fn($x)=>'m.'.$x,$c['search'])).' LIKE ?)'; foreach($c['search'] as $x)$params[]='%'.$search.'%'; }
        $status=(string)($f['status']??'');if($status!==''){ $where[]='m.status=?';$params[]=$status; }
        $whereSql=$where?' WHERE '.implode(' AND ',$where):'';
        $count=$this->pdo->prepare('SELECT COUNT(*) FROM '.$c['table'].' m'.$whereSql);$count->execute($params);$total=(int)$count->fetchColumn();
        $perPage=max(1,$perPage);$pages=max(1,(int)ceil($total/$perPage));$page=min(max(1,$page),$pages);$offset=($page-1)*$perPage;
        $rows=$this->pdo->prepare('SELECT m.* FROM '.$c['table'].' m'.$whereSql.' ORDER BY m.id DESC LIMIT '.$perPage.' OFFSET '.$offset);$rows->execute($params);
        return ['items'=>$rows->fetchAll(),'total'=>$total,'page'=>$page,'pages'=>$pages,'per_page'=>$perPage];
    }
    public function find(string $type,int $id): ?array { $c=$this->config($type);$s=$this->pdo->prepare('SELECT * FROM '.$c['table'].' WHERE id=?');$s->execute([$id]);return $s->fetch()?:null; }
    public function nextCode(string $type): string { $c=$this->config($type);$prefix=['products'=>'PRD','customers'=>'CUS','suppliers'=>'SUP','warehouses'=>'WH'][$type];$start=strlen($prefix)+1;$s=$this->pdo->prepare('SELECT MAX(CAST(SUBSTRING(code,?) AS UNSIGNED)) FROM '.$c['table'].' WHERE code REGEXP ?');$s->execute([$start,'^'.$prefix.'[0-9]+$']);return $prefix.str_pad((string)((int)$s->fetchColumn()+1),3,'0',STR_PAD_LEFT); }
    public function create(string $type,array $d): int { $c=$this->config($type);$fields=$c['fields'];$sql='INSERT INTO '.$c['table'].'('.implode(',',$fields).') VALUES('.implode(',',array_fill(0,count($fields),'?')).')';$s=$this->pdo->prepare($sql);$s->execute(array_map(fn($f)=>$d[$f]??null,$fields));return (int)$this->pdo->lastInsertId(); }
    public function update(string $type,int $id,array $d): void { $c=$this->config($type);$fields=$c['fields'];$sql='UPDATE '.$c['table'].' SET '.implode(',',array_map(fn($f)=>$f.'=?',$fields)).' WHERE id=?';$p=array_map(fn($f)=>$d[$f]??null,$fields);$p[]=$id;$this->pdo->prepare($sql)->execute($p); }
    public function delete(string $type,int $id): void { $c=$this->config($type);$this->pdo->prepare('DELETE FROM '.$c['table'].' WHERE id=?')->execute([$id]); }
    public function hasReferences(string $type,int $id): bool {
        $map=['products'=>[['purchase_order_details','product_id'],['sales_order_details','product_id'],['stocks','product_id'],['stock_movements','product_id']], 'suppliers'=>[['purchase_orders','supplier_id']], 'customers'=>[['sales_orders','customer_id']], 'warehouses'=>[['purchase_orders','warehouse_id'],['sales_orders','warehouse_id'],['stocks','warehouse_id'],['stock_movements','warehouse_id']]];
        foreach($map[$type]??[] as [$table,$col]){$s=$this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$col}=?");$s->execute([$id]);if((int)$s->fetchColumn()>0)return true;}return false;
    }
}
