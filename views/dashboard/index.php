<?php
$summary = $summary ?? [];
$inventory = $inventory ?? [];
$nearest = $nearest ?? [];
$lowStock = $lowStock ?? [];
$salesOrders = $salesOrders ?? [];
$orders = $orders ?? [];
$queues = $queues ?? ['receipts' => [], 'issues' => []];
$isAdmin = $isAdmin ?? false;
$isWarehouse = $isWarehouse ?? false;
function dashboardNumber($value): string { return number_format((float) ($value ?? 0), 0, ',', '.'); }
function dashboardStatusClass(string $status): string { return strtolower(str_replace(' ', '-', $status)); }
?>
<div class="dashboard-intro"><p class="muted">Ringkasan <?= $isAdmin ? 'operasional gudang, order, dan inventori' : ($isWarehouse ? 'antrean penerimaan dan pengeluaran barang' : 'task dan Sales Order milik Anda') ?>.</p><div class="service-status" aria-live="polite"><span class="service-dot is-pending" data-health-dot></span><span data-health-text>Memeriksa layanan…</span></div></div>

<?php if (!$isWarehouse): ?>
<div class="stats-grid">
<?php foreach ([['📁','Project Aktif',$summary['active_projects']??0],['📝','To Do',$summary['todo']??0],['⏳','In Progress',$summary['progress']??0],['✅','Done',$summary['done']??0],['⚠️','Overdue',$summary['overdue']??0]] as [$icon,$label,$value]): ?><div class="stat-card"><span class="stat-icon"><?= $icon ?></span><div><strong><?= dashboardNumber($value) ?></strong><small><?= $label ?></small></div></div><?php endforeach; ?>
<?php if ($isAdmin): foreach ([['📦','Produk Aktif',$inventory['products']??0],['⬇️','Stok Masuk',$inventory['stock_in']??0],['⬆️','Stok Keluar',$inventory['stock_out']??0],['📊','Unit Stok',$inventory['current_stock']??0],['💰','Nilai Inventori',$inventory['inventory_value']??0],['🚨','Baris Stok Rendah',$inventory['low_stock']??0]] as [$icon,$label,$value]): ?><div class="stat-card"><span class="stat-icon"><?= $icon ?></span><div><strong><?= $label==='Nilai Inventori'?'Rp ':'' ?><?= dashboardNumber($value) ?></strong><small><?= $label ?></small></div></div><?php endforeach; endif; ?>
<?php if (!$isAdmin): foreach (['Draft','PendingApproval','Approved','Fulfilled','Cancelled'] as $status): ?><div class="stat-card"><span class="stat-icon">🧾</span><div><strong><?= dashboardNumber($salesOrders[$status]??0) ?></strong><small>SO <?= e($status) ?></small></div></div><?php endforeach; endif; ?>
</div>
<?php endif; ?>

<?php if ($isAdmin): ?>
<section class="card"><div class="card-header"><div><h2>Status Order</h2><p class="muted">Jumlah order menurut status.</p></div></div><div class="stats-grid"><?php foreach ($orders['purchase']??[] as $status=>$count): ?><div class="stat-card"><span class="stat-icon">📥</span><div><strong><?= dashboardNumber($count) ?></strong><small>PO <?= e($status) ?></small></div></div><?php endforeach; ?><?php foreach ($orders['sales']??[] as $status=>$count): ?><div class="stat-card"><span class="stat-icon">📤</span><div><strong><?= dashboardNumber($count) ?></strong><small>SO <?= e($status) ?></small></div></div><?php endforeach; ?></div></section>
<?php endif; ?>

<?php if ($isAdmin || $isWarehouse): ?>
<section class="card"><div class="card-header"><div><h2>Stok Rendah</h2><p class="muted">Stok produk pada gudang yang sudah mencapai batas minimum.</p></div><a class="btn btn-primary" href="?page=stock">Lihat Stok</a></div><div class="table-wrap"><table><thead><tr><th>Kode</th><th>Produk</th><th>Gudang</th><th>Stok</th><th>Minimum</th></tr></thead><tbody><?php foreach ($lowStock as $row): ?><tr><td><?= e($row['product_code']??'-') ?></td><td><?= e($row['product_name']??'-') ?></td><td><?= e($row['warehouse_name']??'-') ?></td><td><span class="badge priority-high"><?= dashboardNumber($row['current_stock']??0) ?> <?= e($row['unit']??'') ?></span></td><td><?= dashboardNumber($row['minimum_stock']??0) ?></td></tr><?php endforeach; ?><?php if (!$lowStock): ?><tr><td colspan="5" class="empty">Tidak ada stok rendah.</td></tr><?php endif; ?></tbody></table></div></section>
<?php endif; ?>

<?php if ($isWarehouse): ?>
<div class="report-grid"><section class="card"><div class="card-header"><h2>Antrean Penerimaan</h2></div><div class="table-wrap"><table><thead><tr><th>PO</th><th>Supplier</th><th>Gudang</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($queues['receipts'] as $row): ?><tr><td><?= e($row['po_number']) ?></td><td><?= e($row['supplier_name']) ?></td><td><?= e($row['warehouse_name']) ?></td><td><?= e($row['status']) ?></td><td><a class="btn btn-sm" href="?page=purchase&action=view&id=<?=(int)$row['id']?>">Buka</a></td></tr><?php endforeach; ?><?php if (!$queues['receipts']): ?><tr><td colspan="5" class="empty">Tidak ada PO menunggu barang.</td></tr><?php endif; ?></tbody></table></div></section>
<section class="card"><div class="card-header"><h2>Antrean Pengeluaran</h2></div><div class="table-wrap"><table><thead><tr><th>SO</th><th>Customer</th><th>Gudang</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($queues['issues'] as $row): ?><tr><td><?= e($row['so_number']) ?></td><td><?= e($row['customer_name']) ?></td><td><?= e($row['warehouse_name']) ?></td><td><?= e($row['status']) ?></td><td><a class="btn btn-sm" href="?page=sales&action=view&id=<?=(int)$row['id']?>">Buka</a></td></tr><?php endforeach; ?><?php if (!$queues['issues']): ?><tr><td colspan="5" class="empty">Tidak ada SO menunggu goods issue.</td></tr><?php endif; ?></tbody></table></div></section></div>
<?php elseif ($nearest): ?>
<section class="card"><div class="card-header"><div><h2>Due Date Terdekat</h2><p class="muted">Lima task aktif yang paling dekat jatuh tempo.</p></div><a class="btn" href="?page=tasks">Lihat Task</a></div><div class="table-wrap"><table><thead><tr><th>Task</th><th>Project</th><th>Status</th><th>Due Date</th></tr></thead><tbody><?php foreach ($nearest as $task): $isOverdue=($task['due_date']??'')<date('Y-m-d'); ?><tr class="<?= $isOverdue?'row-overdue':'' ?>"><td><a class="link" href="?page=tasks&action=view&id=<?= (int)($task['id']??0) ?>"><?= e($task['title']??'-') ?></a></td><td><?= e($task['project_name']??'-') ?></td><td><?= e($task['status']??'-') ?></td><td><?= e($task['due_date']??'-') ?><?= $isOverdue?' ⚠️':'' ?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php endif; ?>
