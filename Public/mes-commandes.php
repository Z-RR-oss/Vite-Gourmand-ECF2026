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
.navbar{
    background-color: #111;
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 30px;

    display: flex;
    justify-content: space-between;
    align-items: center;
}

.navbar a{
    background: transparent;
    margin-left: 10px;
}

.navbar a:hover{
    color: orange;
}
.commande{
    transition: 0.2s;
}

.commande:hover{
    transform: translateY(-3px);
}

button{
    background: green;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
}

footer{
    text-align: center;
    margin-top: 50px;
    color: gray;
}
@media (max-width: 768px){

    .navbar{
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }

    .commande{
        padding: 10px;
    }

    a{
        display: block;
        margin-top: 10px;
    }

}

.statut{
    margin-top: 10px;
}
</style>
    <meta charset="UTF-8">
    <title>Mes commandes</title>
</head>
<body>
    <h1>Vite Gourmand</h1>
    <div class="navbar">
        <h1>Bienvenue <?php echo $_SESSION['email']; ?> 👋</h1>
    <h2>🍽️ Vite Gourmand</h2>

    <div>
        <a href="index.php">Accueil</a>
        <a href="mes-commandes.php">Mes commandes</a>
        <a href="admin-commandes.php">Admin</a>
    </div>
</div>

<h1>Mes commandes</h1>
<?php if (empty($commandes)): ?>

    <p>Aucune commande pour le moment.</p>

<?php endif; ?>

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
<footer>
    <p>© 2026 Vite Gourmand - Tous droits réservés</p>
</footer>
</html>