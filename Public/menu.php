<?php
require_once '../Config/database.php';
$id = $_GET['id'];
echo $id;

$sql = "SELECT * FROM menus WHERE id = :id";
$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$menu = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h1>" . $menu['titre'] . "</h1>";
echo "<p>" . $menu['description'] . "</p>";
echo "<p>Prix : " . $menu['prix'] . " €</p>";
?>
<a href="commander.php?id=<?php echo $menu['id']; ?>">
    Commander ce menu
</a>