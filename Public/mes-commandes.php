<?php
session_start();
require_once '../Config/database.php';

// Sécurité : vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    echo "Accès refusé";
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les commandes du user
$sql =  "SELECT menus.titre, commandes.nb_personnes, commandes.prix_total, commandes.id
        FROM menus
        INNER JOIN commandes
        ON commandes.menu_id = menus.id
        WHERE commandes.user_id = :user_id";
          
          
        

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':user_id' => $user_id
]);

$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes commandes</title>
</head>
<body>

<h1>Mes commandes</h1>

<?php foreach ($commandes as $une_commande): ?> 
    <div>
        <h2>Menu  : <?php echo $une_commande['titre']; ?></h2>
        <p>Nombre de personnes : <?php echo $une_commande['nb_personnes']; ?></p>
        <p>Prix total : <?php echo $une_commande['prix_total']; ?> €</p>
        <a href="supprimer-commande.php?id=<?php echo$une_commande['id'];?> ">Annuler</a>
        <a href="modifier-commande.php?id=<?php echo$une_commande['id'];?>">Modifier la commande</a>
        <hr>
    </div>


<?php endforeach; ?>



</body>
</html>