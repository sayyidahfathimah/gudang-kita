<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=e($pageTitle??'Gudang Kita')?></title>
<link rel="icon" type="image/svg+xml" href="assets/images/gudang-kita.svg">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-wrapper">
<aside class="sidebar" id="sidebar">
 <div class="brand"><span>📦</span><strong>Gudang Kita</strong></div>
 <nav>
  <a class="nav-item <?=($_GET['page']??'dashboard')==='dashboard'?'active':''?>" href="?page=dashboard">🏠 Dashboard</a>
  <?php if(($currentUser['role']??'')==='Admin'): ?><div class="nav-label">MANAGEMENT</div><a class="nav-item <?=($_GET['page']??'')==='users'?'active':''?>" href="?page=users">👥 Users</a><?php endif; ?>
  <?php if(in_array(($currentUser['role']??''),['Admin','WarehouseStaff'],true)): ?>
   <div class="nav-label">MASTER DATA</div>
   <?php foreach([['products','📦 Produk'],['customers','👤 Customer'],['suppliers','🚚 Supplier'],['warehouses','🏢 Gudang']] as [$mt,$ml]):?><a class="nav-item <?=($_GET['page']??'')==='masters'&&($_GET['type']??'')===$mt?'active':''?>" href="?page=masters&type=<?=$mt?>"><?=$ml?></a><?php endforeach;?>
   <div class="nav-label">TRANSAKSI</div>
   <a class="nav-item <?=($_GET['page']??'')==='purchase'?'active':''?>" href="?page=purchase">🛒 Purchase Order</a>
   <a class="nav-item <?=($_GET['page']??'')==='sales'?'active':''?>" href="?page=sales">💰 Sales Order</a>
   <div class="nav-label">INVENTORY</div>
   <a class="nav-item <?=($_GET['page']??'')==='stock'?'active':''?>" href="?page=stock">📊 Stok</a>
   <a class="nav-item <?=($_GET['page']??'')==='movements'?'active':''?>" href="?page=movements">🔄 Stock Movement</a>
   <?php if(($currentUser['role']??'')==='Admin'): ?><div class="nav-label">REPORT</div><a class="nav-item <?=($_GET['page']??'')==='reports'?'active':''?>" href="?page=reports">📋 Laporan</a><?php endif; ?>
  <?php endif; ?>
  <?php if(in_array(($currentUser['role']??''),['Admin','Member'],true)): ?><div class="nav-label">PROJECT MANAGEMENT</div><a class="nav-item <?=($_GET['page']??'')==='projects'?'active':''?>" href="?page=projects">📁 Projects</a><a class="nav-item <?=($_GET['page']??'')==='tasks'?'active':''?>" href="?page=tasks">✅ Tasks</a><?php endif; ?>
 </nav>
</aside>

<main class="main-content">
<header class="topbar">
 <div class="topbar-left">
  <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')">☰</button>
  <h1><?=e($pageTitle??'')?></h1>
 </div>
 <div class="topbar-right">
  <div class="topbar-user">
   <div class="avatar"><?=e(strtoupper(substr($currentUser['name']??'U',0,1)))?></div>
   <div class="topbar-user-info">
    <strong><?=e($currentUser['name']??'')?></strong>
    <small><?=e($currentUser['role']??'')?></small>
   </div>
  </div>
  <form method="post" action="?page=logout">
   <?=$csrfField?>
   <button class="btn btn-logout-top" type="submit">Logout</button>
  </form>
 </div>
</header>
<section class="content">
<?php if($m=getFlash('success')):?><div class="alert success"><?=e($m)?></div><?php endif;?>
<?php if($m=getFlash('error')):?><div class="alert danger"><?=e($m)?></div><?php endif;?>
<?php include_once BASE_PATH.'/views/'.str_replace('.','/',$view??'dashboard/index').'.php';?>
</section>
</main>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
