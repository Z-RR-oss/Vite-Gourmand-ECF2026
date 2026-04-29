<?php
require_once '../Config/database.php';

$sql = "SELECT * FROM menus";
$stmt = $pdo->query($sql);
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<body>

<?php foreach ($menus as $menu): ?>

     <div>
    <h1><?php echo $menu['titre']; ?></h1>
    <h2><?php echo $menu['prix']; ?> €</h2>
    <p><?php echo $menu['description']; ?></p>
    <p>Minimum : <?php echo $menu['nb_personnes_min']; ?> personnes</p>
       </div>

<?php endforeach; ?>

</body>
</html>