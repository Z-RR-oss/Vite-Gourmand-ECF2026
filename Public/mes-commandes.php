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
$sql =  "SELECT menus.titre, commandes.nb_personnes, commandes.prix_total, commandes.id , commandes.statut
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
    <style>

body{
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    padding: 20px;
}

h1{
    color: #333;
}

.commande{
    background: white;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.statut{
    font-weight: bold;
    padding: 5px 10px;
    border-radius: 5px;
    display: inline-block;
}

.en-attente{
    background-color: orange;
    color: white;
}

.validee{
    background-color: green;
    color: white;
}

.preparation{
    background-color: blue;
    color: white;
}

.livree{
    background-color: purple;
    color: white;
}

.terminee{
    background-color: gray;
    color: white;
}

a{
    display: inline-block;
    margin-top: 10px;
    margin-right: 10px;
    text-decoration: none;
    background: black;
    color: white;
    padding: 8px 12px;
    border-radius: 5px;
}

</style>
    <meta charset="UTF-8">
    <title>Mes commandes</title>
</head>
<body>
    <h1>Vite Gourmand</h1>

<h1>Mes commandes</h1>

<?php foreach ($commandes as $une_commande): ?> 
    <div class="commande">
        <h2>Menu  : <?php echo $une_commande['titre']; ?></h2>
        <p>Nombre de personnes : <?php echo $une_commande['nb_personnes']; ?></p>
        <p>Prix total : <?php echo $une_commande['prix_total']; ?> €</p>
        <p class="statut">Status : <?php echo $une_commande['statut'];?></p>
        <a href="supprimer-commande.php?id=<?php echo$une_commande['id'];?> ">Annuler</a>
        <a href="modifier-commande.php?id=<?php echo$une_commande['id'];?>">Modifier la commande</a>
        <hr>
    </div>


<?php endforeach; ?>



</body>
</html>