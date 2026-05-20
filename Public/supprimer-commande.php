<?php
require_once '../Config/database.php';
session_start();
$id = $_GET['id'];

if (!is_numeric($id)) {

    echo "ID invalide";
    exit;
}
echo $id;

$stmt = $pdo->prepare (
"DELETE FROM commandes WHERE id = :id
AND user_id = :user_id"

);

$stmt->execute([
':id' => $id,
':user_id' => $user_id
]);
header("Location: mes-commandes.php");
exit;;
