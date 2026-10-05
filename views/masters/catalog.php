<div class="page-actions"><div><h2>Katalog Produk</h2><p class="muted">Daftar produk aktif yang tersedia untuk transaksi.</p></div></div>
<div class="card filter-card">
    <form class="filter-grid" method="get">
        <input type="hidden" name="page" value="masters">
        <input type="hidden" name="action" value="catalog">
        <label>Cari produk<input name="search" value="<?= e($filters['search']) ?>" placeholder="SKU atau nama produk"></label>
        <label>Kategori<select name="category_id"><option value="">Semua kategori</option>
            <?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= $filters['category_id'] == $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?>
        </select></label>
        <?php include BASE_PATH.'/views/masters/sort-field.php'; ?>
        <button class="btn btn-primary">Cari</button>
        <a class="btn" href="?page=masters&amp;action=catalog">Reset</a>
    </form>
</div>
<div class="card"><div class="table-wrap"><table><thead><tr><th>SKU</th><th>Produk</th><th>Kategori</th><th>Unit</th><th>Harga Jual</th></tr></thead><tbody>
<?php foreach ($products as $product): ?><tr><td><?= e($product['code']) ?></td><td><?= e($product['name']) ?></td><td><?= e($product['category_name']) ?></td><td><?= e($product['unit']) ?></td><td>Rp <?= number_format((float) $product['selling_price'], 0, ',', '.') ?></td></tr><?php endforeach; ?>
<?php if (!$products): ?><tr><td colspan="5" class="empty">Belum ada produk aktif dalam katalog.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?= paginationLinks($pagination, array_merge($filters, ['page' => 'masters', 'action' => 'catalog'])) ?>
