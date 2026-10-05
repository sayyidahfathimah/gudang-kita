<label>Urutkan
    <select name="sort">
        <?php foreach (['' => 'Terbaru', 'code_asc' => 'Kode A–Z', 'code_desc' => 'Kode Z–A', 'name_asc' => 'Nama A–Z', 'name_desc' => 'Nama Z–A'] as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= ($filters['sort'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
</label>
