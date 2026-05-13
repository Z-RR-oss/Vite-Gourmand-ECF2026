<?php
session_start();
require_once '../Config/database.php';


    if ($_SESSION['role'] == "admin") {
       
        echo "Bienvenue Admin !";
    } else {
        echo "Accès refusé";
        exit;
    }
$sql = "SELECT commandes.*, menus.titre, users.email
        FROM commandes
        INNER JOIN menus
        ON commandes.menu_id = menus.id
        INNER JOIN users
        ON commandes.user_id = users.id";

$stmt = $pdo->prepare($sql);

$stmt->execute();

$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - Toutes les commandes</title>
</head>
<body>

<h1>Liste des commandes</h1>

<?php foreach ($commandes as $une_commande): ?>

    <div>
        <h2>Menu : <?php echo $une_commande['titre']; ?></h2>

        <p>Client : <?php echo $une_commande['email']; ?></p>

        <p>Nombre de personnes : <?php echo $une_commande['nb_personnes']; ?></p>
         <p>Status : <?php echo $une_commande['statut'];?></p>
        <p>Prix total : <?php echo $une_commande['prix_total']; ?> €</p>
        <a href="valider-commande.php?id=<?php echo $une_commande['id'];?>">Valider</a>

        <hr>
    </div>

<?php endforeach; ?>

</body>
</html>