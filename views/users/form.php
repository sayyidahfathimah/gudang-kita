<div class="form-card card">
    <?php if(isset($errors['form'])):?><div class="alert danger"><?=e($errors['form'])?></div><?php endif;?>
    <form method="post" action="?page=users&amp;action=<?=$mode==='create'?'store':'update&amp;id='.$user['id']?>">
        <?=$csrfField?>
        <div class="form-grid">
            <label>Username
                <input name="username" required value="<?=e($user['username']??'')?>">
                <?php if(isset($errors['username'])):?><small class="field-error"><?=e($errors['username'])?></small><?php endif;?>
            </label>
            <label>Nama<input name="name" required value="<?=e($user['name']??'')?>"><?php if(isset($errors['name'])):?><small class="field-error"><?=e($errors['name'])?></small><?php endif;?></label>
            <label>Email
                <input type="email" name="email" required value="<?=e($user['email']??'')?>">
                <?php if(isset($errors['email'])):?><small class="field-error"><?=e($errors['email'])?></small><?php endif;?>
            </label>
            <label>Role
                <select name="role">
                    <option <?=($user['role']??'')==='Sales'?'selected':''?>>Sales</option>
                    <option value="WarehouseStaff" <?=($user['role']??'')==='WarehouseStaff'?'selected':''?>>Petugas Gudang</option>
                    <option <?=($user['role']??'')==='Admin'?'selected':''?>>Admin</option>
                </select>
                <?php if(isset($errors['role'])):?><small class="field-error"><?=e($errors['role'])?></small><?php endif;?>
            </label>
            <label>Password
                <?php if($mode==='edit'):?><small class="muted">Kosongkan jika tidak diubah.</small><?php endif;?>
                <input type="password" name="password" <?=$mode==='create'?'required':''?>>
                <?php if(isset($errors['password'])):?><small class="field-error"><?=e($errors['password'])?></small><?php endif;?>
            </label>
            <label>Status
                <select name="is_active">
                    <option value="1" <?=($user['is_active']??1)?'selected':''?>>Active</option>
                    <option value="0" <?=!($user['is_active']??1)?'selected':''?>>Inactive</option>
                </select>
            </label>
        </div>
        <div class="form-actions"><a class="btn" href="?page=users">Cancel</a><button class="btn btn-primary">Save</button></div>
    </form>
</div>
