<?php
$summary = $summary ?? [];
$inventory = $inventory ?? [];
$nearest = $nearest ?? [];
$lowStock = $lowStock ?? [];
$isAdmin = $isAdmin ?? false;
function dashboardNumber($value): string { return number_format((float) ($value ?? 0), 0, ',', '.'); }
function dashboardStatusClass(string $status): string { return strtolower(str_replace(' ', '-', $status)); }
?>
<div class="dashboard-intro"><p class="muted">Ringkasan <?= $isAdmin ? 'operasional project, task, dan inventori' : 'task yang ditugaskan kepada Anda' ?>.</p><div class="service-status" aria-live="polite"><span class="service-dot is-pending" data-health-dot></span><span data-health-text>Memeriksa layanan…</span></div></div>

<div class="stats-grid">
<?php foreach ([['📁','Project Aktif',$summary['active_projects']??0],['📝','To Do',$summary['todo']??0],['⏳','In Progress',$summary['progress']??0],['✅','Done',$summary['done']??0],['⚠️','Overdue',$summary['overdue']??0]] as [$icon,$label,$value]): ?><div class="stat-card"><span class="stat-icon"><?= $icon ?></span><div><strong><?= dashboardNumber($value) ?></strong><small><?= $label ?></small></div></div><?php endforeach; ?>
<?php if ($isAdmin): foreach ([['📦','Total Produk',$inventory['products']??0],['⬇️','Stok Masuk',$inventory['stock_in']??0],['⬆️','Stok Keluar',$inventory['stock_out']??0],['📊','Stok Saat Ini',$inventory['current_stock']??0],['🚨','Stok Minimum',$inventory['low_stock']??0]] as [$icon,$label,$value]): ?><div class="stat-card"><span class="stat-icon"><?= $icon ?></span><div><strong><?= dashboardNumber($value) ?></strong><small><?= $label ?></small></div></div><?php endforeach; endif; ?>
</div>

<div class="dashboard-grid <?= $isAdmin ? '' : 'dashboard-grid-single' ?>">
<?php if ($isAdmin): ?><section class="card"><div class="card-header"><div><h2>Stok Hampir Habis</h2><p class="muted">Produk yang perlu segera diperhatikan.</p></div><a class="btn btn-primary" href="?page=stock">Lihat Stok</a></div><div class="table-wrap"><table><thead><tr><th>Kode</th><th>Produk</th><th class="desktop-only">Gudang</th><th>Stok</th><th class="desktop-only">Minimum</th></tr></thead><tbody><?php foreach ($lowStock as $row): ?><tr><td><?= e($row['product_code']??'-') ?></td><td><?= e($row['product_name']??'-') ?></td><td class="desktop-only"><?= e($row['warehouse_name']??'-') ?></td><td><span class="badge priority-high"><?= dashboardNumber($row['current_stock']??0) ?> <?= e($row['unit']??'') ?></span></td><td class="desktop-only"><?= dashboardNumber($row['minimum_stock']??0) ?></td></tr><?php endforeach; ?><?php if (!$lowStock): ?><tr><td colspan="5" class="empty">Tidak ada stok yang mendekati minimum.</td></tr><?php endif; ?></tbody></table></div></section><?php endif; ?>

<section class="card"><div class="card-header"><div><h2>Due Date Terdekat</h2><p class="muted">Lima task aktif yang paling dekat jatuh tempo.</p></div><a class="btn" href="?page=tasks">Lihat Task</a></div><div class="table-wrap"><table><thead><tr><th>Task</th><th class="desktop-only">Project</th><th>Status</th><th>Due Date</th></tr></thead><tbody><?php foreach ($nearest as $task): $isOverdue=($task['due_date']??'')<date('Y-m-d'); ?><tr class="<?= $isOverdue?'row-overdue':'' ?>"><td><a class="link" href="?page=tasks&action=view&id=<?= (int)($task['id']??0) ?>"><?= e($task['title']??'-') ?></a></td><td class="desktop-only"><?= e($task['project_name']??'-') ?></td><td><span class="badge status-<?= e(dashboardStatusClass((string)($task['status']??''))) ?>"><?= e($task['status']??'-') ?></span></td><td><?= e($task['due_date']??'-') ?><?= $isOverdue?' ⚠️':'' ?></td></tr><?php endforeach; ?><?php if (!$nearest): ?><tr><td colspan="4" class="empty">Belum ada task aktif.</td></tr><?php endif; ?></tbody></table></div></section>
</div>
