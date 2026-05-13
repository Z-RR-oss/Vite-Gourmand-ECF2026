<?php
session_start();
require_once '../Config/database.php';

$id = $_GET['id'];
echo $id;


if ($_SERVER ['REQUEST_METHOD'] === 'POST') {
 $nb_personnes = $_POST['nb_personnes'];
 echo $nb_personnes;
$sql = "UPDATE commandes
SET nb_personnes = :nb_personnes
WHERE id = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id,
    ':nb_personnes' => $nb_personnes
]);



}

if (!isset($_SESSION['user_id'])) {
    echo "Accès refusé";
    exit;
}

$sql = "SELECT * FROM commandes WHERE id = :id";
$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$commande = $stmt->fetch(PDO::FETCH_ASSOC);

echo $commande['nb_personnes'];?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>
    <body>
    <form   action="modifier-commande.php?id=<?php echo $id; ?>"  method="POST">
    <input type="number" name="nb_personnes"value="<?php echo$commande['nb_personnes']?>">
    <button type="submit">Envoyer</button>

    </form>
        
</body>
</html>     