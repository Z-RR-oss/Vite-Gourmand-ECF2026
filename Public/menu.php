<?php
require_once '../Config/database.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    exit("Menu introuvable.");
}

// 1. Récupérer le menu
$sql = "SELECT * FROM menus WHERE id = :id";
$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$menu = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. Vérifier que le menu existe
if (!$menu) {
    exit("Menu introuvable.");
}

// 3. Récupérer les plats liés à ce menu
$sqlPlats = "
    SELECT
        plats.id,
        plats.nom,
        plats.description,
        plats.type_plat,
        GROUP_CONCAT(allergenes.nom ORDER BY allergenes.nom SEPARATOR ', ') AS allergenes
    FROM plats
    JOIN menu_plat ON plats.id = menu_plat.plat_id
    LEFT JOIN plat_allergene ON plats.id = plat_allergene.plat_id
    LEFT JOIN allergenes ON plat_allergene.allergene_id = allergenes.id
    WHERE menu_plat.menu_id = :menu_id
    GROUP BY plats.id, plats.nom, plats.description, plats.type_plat, menu_plat.ordre_affichage
    ORDER BY menu_plat.ordre_affichage
";
$stmtPlats = $pdo->prepare($sqlPlats);

$stmtPlats->execute([
    ':menu_id' => $id
]);

$plats = $stmtPlats->fetchAll(PDO::FETCH_ASSOC);
// 4. Récupérer les images du menu
$sqlImages = "SELECT * FROM menu_images WHERE menu_id = :menu_id";

$stmtImages = $pdo->prepare($sqlImages);

$stmtImages->execute([
    ':menu_id' => $id
]);

$images = $stmtImages->fetchAll(PDO::FETCH_ASSOC);

// 4. Affichage
echo "<h1>" . $menu['titre'] . "</h1>";
echo "<p>" . $menu['description'] . "</p>";
echo "<p>Prix : " . $menu['prix'] . " €</p>";
echo "<p>Thème : " . $menu['theme'] . "</p>";
echo "<p>Régime : " . $menu['regime'] . "</p>";
echo "<p>Minimum : " . $menu['nb_personnes_min'] . " personnes</p>";
echo "<p>Stock disponible : " . $menu['stock_disponible'] . "</p>";
echo "<p>Conditions : " . $menu['conditions_menu'] . "</p>";
echo "<p>Délai de commande : " . $menu['delai_commande_heures'] . " heures</p>";
?>

<h2>Galerie</h2>

<?php if (empty($images)): ?>
    <p>Aucune image disponible.</p>
<?php else: ?>

    <?php foreach ($images as $image): ?>
        <img
            src="<?php echo $image['chemin_image']; ?>"
            alt="<?php echo $image['texte_alternatif']; ?>"
            width="300"
        >
    <?php endforeach; ?>

<?php endif; ?>


<h2>Plats du menu</h2>

<?php if (empty($plats)): ?>
    <p>Aucun plat associé à ce menu.</p>
<?php else: ?>

    <?php foreach ($plats as $plat): ?>
        <div>
            <h3><?php echo $plat['nom']; ?></h3>
            <p><?php echo $plat['description']; ?></p>
            <p>Type : <?php echo $plat['type_plat']; ?></p>
            <p>Allergènes :<?php echo $plat['allergenes'] ?: 'Aucun allergène renseigné'; ?></p>
        </div>
    <?php endforeach; ?>

<?php endif; ?>
<a href="commander.php?id=<?php echo $menu['id']; ?>">
    Commander ce menu
</a>