<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Nos menus');
?>
<div class="page-heading"><p class="eyebrow">LA CARTE VITE & GOURMAND</p><h1>Une belle occasion<br>de se retrouver.</h1><p>Découvrez nos menus et composez votre prochain moment de convivialité.</p></div>
<?php require __DIR__ . '/../Templates/menu-list.php';
renderFooter($pdo); ?>
