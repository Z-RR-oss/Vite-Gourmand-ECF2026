<?php
require_once '../Config/database.php';
session_start();
$id = $_GET['id'];
echo $id;

$stmt = $pdo->prepare (
"DELETE FROM commandes WHERE id = ?"
);

$stmt->execute([$id]);  
echo " commande supprimé ";
