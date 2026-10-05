<?php
$isPurchase = $orderKind === 'purchase';
$partyKey = $isPurchase ? 'supplier_id' : 'customer_id';
$partyLabel = $isPurchase ? 'Supplier' : 'Customer';
$parties = $isPurchase ? $suppliers : $customers;
$dateKey = $isPurchase ? 'po_date' : 'so_date';
$priceKey = $isPurchase ? 'purchase_price' : 'selling_price';
$old = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : [];
$rows = is_array($old['product_id'] ?? null) && $old['product_id'] ? $old['product_id'] : [''];
?>
<div class="card form-card">
<form method="post" action="?page=<?= e($orderKind) ?>&amp;action=store">
    <?= $csrfField ?>
    <div class="form-grid">
        <label><?= e($partyLabel) ?><select name="<?= e($partyKey) ?>" required>
            <option value="">Pilih <?= e(strtolower($partyLabel)) ?></option>
            <?php foreach ($parties as $party): ?><option value="<?= (int) $party['id'] ?>" <?= ($old[$partyKey] ?? '') == $party['id'] ? 'selected' : '' ?>><?= e($party['code'].' - '.$party['name']) ?></option><?php endforeach; ?>
        </select></label>
        <label>Gudang<select name="warehouse_id" required><option value="">Pilih gudang</option>
            <?php foreach ($warehouses as $warehouse): ?><option value="<?= (int) $warehouse['id'] ?>" <?= ($old['warehouse_id'] ?? '') == $warehouse['id'] ? 'selected' : '' ?>><?= e($warehouse['code'].' - '.$warehouse['name']) ?></option><?php endforeach; ?>
        </select></label>
        <label>Tanggal<input type="date" name="<?= e($dateKey) ?>" value="<?= e($old[$dateKey] ?? date('Y-m-d')) ?>" required></label>
        <label class="full-field">Catatan<textarea name="notes"><?= e($old['notes'] ?? '') ?></textarea></label>
    </div>
    <h3>Detail Produk</h3>
    <div class="table-wrap"><table id="detailTable">
        <thead><tr><th>Produk</th><th>Qty</th><th>Harga <?= $isPurchase ? 'Beli' : 'Jual' ?></th><th>Aksi</th></tr></thead>
        <tbody><?php foreach ($rows as $index => $productId): ?><tr>
            <td><select name="product_id[]" aria-label="Produk" required><option value="">Pilih produk</option>
                <?php foreach ($products as $product): ?><option value="<?= (int) $product['id'] ?>" data-price="<?= e($product[$priceKey]) ?>" <?= $productId == $product['id'] ? 'selected' : '' ?>><?= e($product['code'].' - '.$product['name']) ?></option><?php endforeach; ?>
            </select></td>
            <td><input type="number" name="qty[]" aria-label="Jumlah" min="0.01" step="0.01" value="<?= e($old['qty'][$index] ?? '') ?>" required></td>
            <td><input type="number" name="price[]" aria-label="Harga" min="0" step="0.01" value="<?= e($old['price'][$index] ?? '') ?>" required></td>
            <td><button type="button" class="btn btn-sm" onclick="this.closest('tr').remove()">Hapus</button></td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
    <button type="button" class="btn" onclick="addDetailRow('detailTable')">+ Tambah Produk</button>
    <div class="form-actions"><a class="btn" href="?page=<?= e($orderKind) ?>">Batal</a><button class="btn btn-primary">Simpan <?= $isPurchase ? 'PO' : 'SO' ?></button></div>
</form>
</div>
<script>
document.addEventListener('change', function (event) {
    if (event.target.matches('select[name="product_id[]"]')) {
        const price = event.target.selectedOptions[0]?.dataset.price;
        if (price !== undefined) event.target.closest('tr').querySelector('input[name="price[]"]').value = price;
    }
});
</script>
