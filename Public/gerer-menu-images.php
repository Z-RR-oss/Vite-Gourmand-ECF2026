<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Templates/layout.php';
require_once __DIR__ . '/../Templates/catalogue.php';
require_once __DIR__ . '/../Services/MenuImages.php';
requireAdminOrEmployee();
$id = positiveId($_GET['id'] ?? null);
$query = $pdo->prepare('SELECT id, titre FROM menus WHERE id = ?');
$query->execute([$id]);
$menu = $query->fetch();
if (!$menu) { abortRequest(404, 'Menu introuvable.'); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';
        if ($action === 'supprimer') {
            $query = $pdo->prepare('DELETE FROM menu_images WHERE id = ? AND menu_id = ?');
            $query->execute([positiveId($_POST['image_id'] ?? null), $id]);
        } elseif ($action === 'ajouter') {
            $alt = trim($_POST['texte_alternatif'] ?? '');
            if ($alt === '' || mb_strlen($alt) > 255) { throw new DomainException('Décrivez l’image en 1 à 255 caractères.'); }
            $path = storeMenuImage($_FILES['image'] ?? []);
            try {
                $query = $pdo->prepare('INSERT INTO menu_images (menu_id, chemin_image, texte_alternatif) VALUES (?, ?, ?)');
                $query->execute([$id, $path, $alt]);
            } catch (Throwable $exception) {
                unlink(__DIR__ . '/' . $path);
                throw $exception;
            }
        } elseif ($action === 'modifier') {
            $alt = trim($_POST['texte_alternatif'] ?? '');
            if ($alt === '' || mb_strlen($alt) > 255) { throw new DomainException('Décrivez l’image en 1 à 255 caractères.'); }
            $query = $pdo->prepare('UPDATE menu_images SET texte_alternatif = ? WHERE id = ? AND menu_id = ?');
            $query->execute([$alt, positiveId($_POST['image_id'] ?? null), $id]);
        } else { throw new DomainException('Action invalide.'); }
        header('Location: gerer-menu-images.php?id=' . $id, true, 303); exit;
    } catch (DomainException $exception) { $error = $exception->getMessage(); }
}
$query = $pdo->prepare('SELECT * FROM menu_images WHERE menu_id = ? ORDER BY id');
$query->execute([$id]); $images = $query->fetchAll();
renderHeader('Images du menu');
?>
<section class="content-panel">
<p class="eyebrow">Le catalogue en images</p><h1>Galerie — <?= e($menu['titre']) ?></h1>
<p>La première image devient la couverture du menu. Les images sont affichées dans leur ordre d’ajout.</p>
<?php if ($error): ?><p class="erreur" role="alert"><?= e($error) ?></p><?php endif; ?>
<div class="menu-grid">
<?php foreach ($images as $item): ?><article class="card">
<img src="<?= e(catalogueImage($item['chemin_image'])) ?>" alt="<?= e($item['texte_alternatif']) ?>" width="300" height="210" class="gallery-preview">
<form method="post"><?= csrfInput() ?><input type="hidden" name="image_id" value="<?= (int) $item['id'] ?>">
<label for="alt-<?= (int) $item['id'] ?>">Description de cette image</label><input id="alt-<?= (int) $item['id'] ?>" name="texte_alternatif" maxlength="255" required value="<?= e($item['texte_alternatif']) ?>">
<button name="action" value="modifier">Enregistrer la description</button><button name="action" value="supprimer" class="button-secondary" formnovalidate>Retirer de la galerie</button></form>
</article><?php endforeach; ?>
</div>
<h2>Ajouter une photo</h2><form method="post" enctype="multipart/form-data"><?= csrfInput() ?>
<label for="image">Image JPEG, PNG ou WebP (5 Mo maximum)</label><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
<label for="texte_alternatif">Description pour les personnes ne voyant pas l’image</label><input id="texte_alternatif" name="texte_alternatif" maxlength="255" required>
<button name="action" value="ajouter">Ajouter l’image</button></form>
<p><a href="admin-menus.php">Retour aux menus</a> · <a href="menu.php?id=<?= $id ?>">Voir la fiche publique</a></p>
</section><?php renderFooter($pdo); ?>
