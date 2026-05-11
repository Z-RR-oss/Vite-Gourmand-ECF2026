<?php
session_start();
require_once '../Config/database.php';
$id = $_GET['id'];
echo $id;

$sql = "SELECT * FROM  menus where id = :id";
$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);
$menu = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$menu) {
    echo "Menu introuvable";
    exit;
}


echo "<h1>" . $menu['titre'] . "</h1>";
echo "<p>" . $menu['description'] . "</p>"; 
echo "<p>Prix : " . $menu['prix'] . " €</p>";


$user_id = $_SESSION['user_id'];
$menu_id = $id;
$nb_personnes = 1;
$prix_total = $menu['prix'];

$sql = "INSERT INTO commandes (user_id, menu_id, nb_personnes, prix_total)
        VALUES (:user_id, :menu_id, :nb_personnes, :prix_total)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':user_id' => $user_id,
    ':menu_id' => $menu_id,
    ':nb_personnes' => $nb_personnes,
    ':prix_total' => $prix_total
]);

echo "Commande validée !";












?>



