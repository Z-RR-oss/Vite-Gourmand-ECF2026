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
    if (!is_numeric($id)) {

    echo "ID invalide";
    exit;
}


    $statutsAutorises = ["validé", "en préparation" ,"livrée" , "terminée"];
     $statut = strtolower($statut);

    if (in_array ($statut, $statutsAutorises)){
        echo "accès autorisé";
    } else {
        echo "Statut invalide";
        exit;
        
    }




$sql = "UPDATE commandes
       SET statut = :statut
       WHERE id= :id
       ";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id,
    ':statut' => $statut
    

    
]);

   header("Location: admin-commandes.php");
exit;;



    