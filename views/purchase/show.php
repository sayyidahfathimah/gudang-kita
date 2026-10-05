<div class="card detail-card">
    <div class="detail-header"><div><h2><?= e($order['po_number']) ?></h2><p class="muted">Purchase Order</p></div><span class="badge status-<?= strtolower($order['status']) ?>"><?= e($order['status']) ?></span></div>
    <div class="detail-grid"><div><small>Supplier</small><strong><?= e($order['supplier_name']) ?></strong></div><div><small>Gudang</small><strong><?= e($order['warehouse_name']) ?></strong></div><div><small>Tanggal</small><strong><?= e($order['po_date']) ?></strong></div><div><small>Total</small><strong>Rp <?= number_format((float) $order['total_amount'], 0, ',', '.') ?></strong></div></div>
</div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>Produk</th><th>Dipesan</th><th>Diterima</th><th>Harga</th><th>Subtotal</th></tr></thead><tbody>
<?php foreach ($order['details'] as $detail): ?>
<tr><td><?= e($detail['product_code'].' - '.$detail['product_name']) ?></td><td><?= number_format((float) $detail['qty'], 2, ',', '.') ?> <?= e($detail['unit']) ?></td><td><?= number_format((float) $detail['received_qty'], 2, ',', '.') ?> <?= e($detail['unit']) ?></td><td>Rp <?= number_format((float) $detail['price'], 0, ',', '.') ?></td><td>Rp <?= number_format((float) $detail['subtotal'], 0, ',', '.') ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<?php if (in_array(($currentUser['role'] ?? ''), ['Admin', 'WarehouseStaff'], true) && in_array($order['status'], ['Ordered', 'PartiallyReceived'], true)): ?>
<div class="card form-card"><h2>Terima Barang</h2><p class="muted">Masukkan jumlah yang diterima pada pengiriman ini. Stok dan riwayat IN dicatat setelah disimpan.</p><form method="post" action="?page=purchase&amp;action=receive&amp;id=<?= $order['id'] ?>"><?= $csrfField ?><div class="table-wrap"><table><thead><tr><th>Produk</th><th>Sisa</th><th>Diterima Sekarang</th></tr></thead><tbody>
<?php foreach ($order['details'] as $detail): $remaining = (float) $detail['qty'] - (float) $detail['received_qty']; ?>
<tr><td><?= e($detail['product_code'].' - '.$detail['product_name']) ?></td><td><?= number_format($remaining, 2, ',', '.') ?> <?= e($detail['unit']) ?></td><td><input type="number" name="received_qty[<?= $detail['id'] ?>]" min="0" max="<?= $remaining ?>" step="0.01" value="0" <?= $remaining <= 0 ? 'readonly' : '' ?>></td></tr>
<?php endforeach; ?>
</tbody></table></div><div class="form-actions"><a class="btn" href="?page=purchase">Kembali</a><button class="btn btn-primary">Catat penerimaan</button></div></form></div>
<?php endif; ?>
