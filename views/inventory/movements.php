<div class="page-actions">
  <div><p class="muted">Riwayat penerimaan, pengeluaran, dan penyesuaian saldo awal.</p></div>
  <a class="btn" href="?page=stock">← Kembali ke Stok</a>
</div>
<div class="card filter-card">
  <form class="filter-grid" method="get">
    <input type="hidden" name="page" value="movements">
    <label for="movement-product">Produk</label><select id="movement-product" name="product_id"><option value="">Semua produk</option><?php foreach ($products as $p): ?><option value="<?=e($p['id'])?>" <?=$filters['product_id'] == $p['id'] ? 'selected' : ''?>><?=e($p['code'].' - '.$p['name'])?></option><?php endforeach; ?></select>
    <label for="movement-warehouse">Gudang</label><select id="movement-warehouse" name="warehouse_id"><option value="">Semua gudang</option><?php foreach ($warehouses as $w): ?><option value="<?=e($w['id'])?>" <?=$filters['warehouse_id'] == $w['id'] ? 'selected' : ''?>><?=e($w['name'])?></option><?php endforeach; ?></select>
    <button class="btn btn-primary">Filter</button><a class="btn" href="?page=movements">Reset</a>
  </form>
</div>
<div class="card"><div class="table-wrap"><table>
  <thead><tr><th>Tanggal</th><th>Tipe</th><th>Nomor Referensi</th><th>Produk</th><th>Gudang</th><th>Dilakukan oleh</th><th>Qty</th><th>Catatan</th></tr></thead>
  <tbody>
  <?php foreach ($result['items'] as $r):
      $type = (string) $r['transaction_type'];
      $direction = (string) ($r['adjustment_direction'] ?? '');
      $label = $type;
      if ($type === 'Adjustment') {
          $label = $direction === 'Increase' ? 'Penyesuaian Masuk' : 'Penyesuaian Keluar';
      }
      $positive = $type === 'Receipt' || ($type === 'Adjustment' && $direction === 'Increase');
  ?>
    <tr>
      <td><?=e($r['movement_date'])?></td>
      <td><span class="badge <?=$positive ? 'status-active' : 'priority-medium'?>"><?=e($label)?></span></td>
      <td><?=e($r['transaction_number'])?></td>
      <td><?=e($r['product_code'].' - '.$r['product_name'])?></td>
      <td><?=e($r['warehouse_name'])?></td>
      <td><?=e($r['actor_name'] ?? 'Data awal / tidak tercatat')?></td>
      <td><?=number_format((float) $r['qty'], 2, ',', '.')?></td>
      <td><?=e($r['note'] ?? '')?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$result['items']): ?><tr><td colspan="8" class="empty">Belum ada movement.</td></tr><?php endif; ?>
  </tbody>
</table></div><?=paginationLinks($result, ['page' => 'movements', 'product_id' => $filters['product_id'], 'warehouse_id' => $filters['warehouse_id']])?></div>
