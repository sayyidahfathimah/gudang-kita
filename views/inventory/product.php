<div class="page-actions"><a class="btn" href="?page=stock">← Kembali ke stok</a></div>
<div class="card detail-card">
    <div class="detail-header"><div><h2><?= e($product['code']) ?> — <?= e($product['name']) ?></h2><p class="muted">Stok total: <strong><?= number_format((float) $product['total_stock'], 2, ',', '.') ?> <?= e($product['unit']) ?></strong> · Reorder point: <?= number_format((float) $product['minimum_stock'], 2, ',', '.') ?></p></div><span class="badge status-<?= strtolower($product['status']) ?>"><?= e($product['status']) ?></span></div>
    <div class="table-wrap"><table><thead><tr><th>Gudang</th><th>Stok masuk</th><th>Stok keluar</th><th>Stok saat ini</th><th>Terakhir diperbarui</th></tr></thead><tbody>
    <?php foreach ($product['warehouses'] as $warehouse): ?><tr><td><?= e($warehouse['warehouse_name']) ?></td><td><?= number_format((float) $warehouse['stock_in'], 2, ',', '.') ?> <?= e($product['unit']) ?></td><td><?= number_format((float) $warehouse['stock_out'], 2, ',', '.') ?> <?= e($product['unit']) ?></td><td><?= number_format((float) $warehouse['current_stock'], 2, ',', '.') ?> <?= e($product['unit']) ?></td><td><?= e($warehouse['updated_at'] ?? 'Belum ada transaksi') ?></td></tr><?php endforeach; ?>
    <?php if (!$product['warehouses']): ?><tr><td colspan="5" class="empty">Belum ada data gudang.</td></tr><?php endif; ?>
    </tbody></table></div>
</div>
