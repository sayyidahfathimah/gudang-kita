<div class="page-actions"><div><p class="muted">
<?php if ($role === 'Sales'): ?>Laporan Sales Order yang Anda buat.
<?php elseif ($role === 'WarehouseStaff'): ?>Ringkasan stok dan riwayat pergerakan barang.
<?php else: ?>Ringkasan stok, stock ledger, pembelian, dan penjualan.
<?php endif; ?></p></div></div>
<div class="card filter-card">
  <form method="get" class="filter-grid">
    <input type="hidden" name="page" value="reports">
    <label>Dari tanggal<input type="date" name="from" value="<?=e($filters['from'])?>" required></label>
    <label>Sampai tanggal<input type="date" name="to" value="<?=e($filters['to'])?>" required></label>
    <button class="btn btn-primary">Terapkan</button>
  </form>
  <div class="actions report-downloads">
    <?php if ($role !== 'Sales'): ?>
      <a class="btn" href="?page=reports&amp;format=csv&amp;report=movements&amp;from=<?=e($filters['from'])?>&amp;to=<?=e($filters['to'])?>">Unduh Stock Ledger CSV</a>
      <a class="btn" href="?page=reports&amp;format=csv&amp;report=stock&amp;from=<?=e($filters['from'])?>&amp;to=<?=e($filters['to'])?>">Unduh Saldo Stok CSV</a>
    <?php endif; ?>
    <?php if ($role !== 'WarehouseStaff'): ?>
      <a class="btn" href="?page=reports&amp;format=csv&amp;report=orders&amp;from=<?=e($filters['from'])?>&amp;to=<?=e($filters['to'])?>">Unduh Status Order CSV</a>
    <?php endif; ?>
  </div>
</div>
<div class="report-grid">
  <?php if ($role !== 'Sales'): ?>
  <div class="card"><div class="card-header"><h2>Ringkasan Stok Saat Ini</h2></div>
    <div class="table-wrap"><table><thead><tr><th>Kode</th><th>Produk</th><th>Gudang</th><th>Stok</th><th>Minimum</th></tr></thead><tbody>
    <?php foreach ($stock as $row): ?><tr><td><?=e($row['code'])?></td><td><?=e($row['name'])?></td><td><?=e($row['warehouse_name'])?></td><td><?=number_format((float)$row['current_stock'],2,',','.')?></td><td><?=number_format((float)$row['minimum_stock'],2,',','.')?></td></tr><?php endforeach; ?>
    <?php if (!$stock): ?><tr><td colspan="5" class="empty">Belum ada saldo stok.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
  <?php endif; ?>
  <?php if ($role === 'Admin'): ?>
  <div class="card"><div class="card-header"><h2>Purchase Order Diterima per Bulan</h2></div>
    <div class="table-wrap"><table><thead><tr><th>Periode</th><th>Order</th><th>Total</th></tr></thead><tbody>
    <?php foreach ($purchase as $row): ?><tr><td><?=e($row['period'])?></td><td><?=e($row['orders'])?></td><td>Rp <?=number_format((float)$row['total'],0,',','.')?></td></tr><?php endforeach; ?>
    <?php if (!$purchase): ?><tr><td colspan="3" class="empty">Tidak ada penerimaan pada rentang tanggal ini.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
  <?php endif; ?>
  <?php if ($role !== 'WarehouseStaff'): ?>
  <div class="card"><div class="card-header"><h2><?= $role === 'Sales' ? 'Sales Order Saya' : 'Sales Order Fulfilled per Bulan' ?></h2></div>
    <div class="table-wrap"><table><thead><tr><th>Periode</th><th>Order</th><th>Total</th></tr></thead><tbody>
    <?php foreach ($sales as $row): ?><tr><td><?=e($row['period'])?></td><td><?=e($row['orders'])?></td><td>Rp <?=number_format((float)$row['total'],0,',','.')?></td></tr><?php endforeach; ?>
    <?php if (!$sales): ?><tr><td colspan="3" class="empty">Tidak ada penjualan fulfilled pada rentang tanggal ini.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
  <?php endif; ?>
</div>
