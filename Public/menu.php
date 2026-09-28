<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Templates/layout.php';
require_once __DIR__ . '/../Templates/catalogue.php';
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$query = $pdo->prepare('SELECT * FROM menus WHERE id = :id AND actif = 1');
$query->execute(['id' => $id ?: 0]);
$menu = $query->fetch(PDO::FETCH_ASSOC);
if (!$menu) {
    http_response_code(404);
    renderHeader('Menu introuvable');
    echo '<div class="empty-state"><h1>Ce menu n’est pas disponible.</h1><p>Retrouvez nos autres propositions dans la carte.</p><a class="button" href="menus.php">Voir les menus</a></div>';
    renderFooter($pdo);
    exit;
}
$query = $pdo->prepare("SELECT p.id, p.nom, p.description, p.type_plat, GROUP_CONCAT(a.nom ORDER BY a.nom SEPARATOR ', ') AS allergenes
    FROM plats p JOIN menu_plat mp ON p.id = mp.plat_id LEFT JOIN plat_allergene pa ON p.id = pa.plat_id LEFT JOIN allergenes a ON pa.allergene_id = a.id
    WHERE mp.menu_id = :menu_id GROUP BY p.id, p.nom, p.description, p.type_plat, mp.ordre_affichage ORDER BY mp.ordre_affichage, p.id");
$query->execute(['menu_id' => $id]);
$plats = $query->fetchAll(PDO::FETCH_ASSOC);
$query = $pdo->prepare('SELECT chemin_image, texte_alternatif FROM menu_images WHERE menu_id = :menu_id ORDER BY id');
$query->execute(['menu_id' => $id]);
$images = $query->fetchAll(PDO::FETCH_ASSOC);
renderHeader($menu['titre']);
?>
<nav class="breadcrumbs" aria-label="Fil d’Ariane"><a href="index.php">Accueil</a><span aria-hidden="true">/</span><a href="menus.php">Nos menus</a><span aria-hidden="true">/</span><span><?= e($menu['titre']) ?></span></nav>
<section class="menu-detail">
    <div class="menu-gallery"><img class="detail-cover" src="<?= e(catalogueImage($images[0]['chemin_image'] ?? null)) ?>" alt="<?= e($images[0]['texte_alternatif'] ?? 'Illustration de notre table à partager') ?>" width="800" height="650"><?php if (count($images) > 1): ?><div class="gallery-thumbnails"><?php foreach (array_slice($images, 1) as $image): ?><a href="<?= e(catalogueImage($image['chemin_image'])) ?>"><img src="<?= e(catalogueImage($image['chemin_image'])) ?>" alt="<?= e($image['texte_alternatif']) ?>" width="220" height="170" loading="lazy"></a><?php endforeach; ?></div><?php endif; ?><p class="small-note">Visuels de présentation. Retrouvez la composition ci-dessous.</p></div>
    <div class="menu-detail-copy"><p class="eyebrow"><?= e($menu['theme']) ?> <span aria-hidden="true">·</span> <?= e($menu['regime']) ?></p><h1><?= e($menu['titre']) ?></h1><p class="lead"><?= nl2br(e($menu['description'])) ?></p><div class="detail-price"><strong><?= number_format((float) $menu['prix'], 2, ',', ' ') ?> €</strong><span>pour <?= (int) $menu['nb_personnes_min'] ?> personnes minimum</span></div><dl class="menu-facts"><div><dt>À partir de</dt><dd><?= number_format((float) $menu['prix'] / max(1, (int) $menu['nb_personnes_min']), 2, ',', ' ') ?> € / personne</dd></div><div><dt>Disponibilités</dt><dd><?= (int) $menu['stock_disponible'] ?> commande(s)</dd></div><div><dt>Anticipation</dt><dd><?= (int) $menu['delai_commande_heures'] ?> heures minimum</dd></div></dl><aside class="menu-conditions"><h2>Avant de commander</h2><p><?= nl2br(e($menu['conditions_menu'] ?: 'Consultez notre équipe pour les conditions de conservation.')) ?></p><p>À commander au moins <?= (int) $menu['delai_commande_heures'] ?> heures avant la prestation.</p></aside><?php if ((int) $menu['stock_disponible'] > 0): ?><a class="button button-wide" href="commander.php?id=<?= (int) $menu['id'] ?>">Commander ce menu <span aria-hidden="true">↗</span></a><?php else: ?><button class="button-wide" disabled>Momentanément indisponible</button><?php endif; ?><p class="small-note">10 % de remise dès <?= (int) $menu['nb_personnes_min'] + 5 ?> convives. Livraison hors Bordeaux : 5 € + 0,59 €/km.</p></div>
</section>
<section class="dishes-section"><p class="eyebrow">LE PLAISIR, DE L’ENTRÉE AU DESSERT</p><h2>À votre menu.</h2><?php if (!$plats): ?><p>La composition sera précisée par notre équipe. <a href="contact.php">Contactez-nous pour en savoir plus.</a></p><?php endif; ?><div class="dish-list"><?php foreach ($plats as $plat): ?><article class="dish"><span class="dish-type"><?= e(['entree' => 'Entrée', 'plat' => 'Plat', 'dessert' => 'Dessert'][$plat['type_plat']] ?? $plat['type_plat']) ?></span><div><h3><?= e($plat['nom']) ?></h3><p><?= e($plat['description']) ?></p><p class="allergens"><strong>Allergènes :</strong> <?= e($plat['allergenes'] ?: 'Non renseignés — contactez-nous en cas d’allergie.') ?></p></div></article><?php endforeach; ?></div></section>
<?php renderFooter($pdo); ?>
