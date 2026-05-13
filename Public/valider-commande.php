<?php
session_start();
require_once '../Config/database.php';
$id = $_GET['id'];
echo $id;

    if ($_SESSION['role'] == "admin") {
       
        echo "Bienvenue Admin !";
    } else {
        echo "Accès refusé";
        exit;
    }

$sql = "UPDATE commandes
       SET statut = 'validée'
       WHERE id= :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id,
    
]);

    echo "Commande validée";

    