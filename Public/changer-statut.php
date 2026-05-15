<?php
session_start();
require_once '../Config/database.php';


    if ($_SESSION['role'] == "admin") {
       
        echo "Bienvenue Admin !";
    } else {
        echo "Accès refusé";
        exit;
    }

    $id = $_GET['id'];
    $statut = $_GET['statut'];




$sql = "UPDATE commandes
       SET statut = :statut
       WHERE id= :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id,
    ':statut' => $statut
    
]);

    echo "la commande est $statut";