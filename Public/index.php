<?php
require_once '../Config/database.php';

$sql = "SELECT * FROM menus";
$stmt = $pdo->query($sql);
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
   <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vite & Gourmand</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
   <nav class="navbar">

    <div class="navbar-logo">
        <a href="index.php">
            <img src="LogoVG.png" alt="Logo Vite & Gourmand" width="150">
        </a>

        <h1>
            <a href="index.php">Vite & Gourmand</a>
        </h1>
    </div>

    <div class="navbar-links">
        <a href="index.php#menus">Menus</a>
        <a href="mes-commandes.php">Mes commandes</a>
    </div>

    <div class="navbar-user">
        <a href="login.php">Connexion</a>
    </div>

</nav>
<section class="hero">
    <div class="texte">
 <h1>Des plats gourmands,prêts sans attendre</h1>
 <p>   Commandez simplement vos menus
    et profitez d'un moment convivial.</p>
    </div>
  
<a href="index.php#menus">Découvrir nos menus</a>
</div>
  <div class="image">

<img src="" alt="">
</div>

</section>



</section>
<section id="menus">
<?php foreach ($menus as $menu): ?>

     <div>
    <h1><?php echo $menu['titre']; ?></h1>
    <h2><?php echo $menu['prix']; ?> €</h2>
    <p><?php echo $menu['description']; ?></p>
    <p>Minimum : <?php echo $menu['nb_personnes_min']; ?> personnes</p>
    <a href="menu.php?id=<?php echo $menu['id']; ?>">Afficher le menu</a>
       </div>

<?php endforeach; ?>
</section>

</body>
</html>