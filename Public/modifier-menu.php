<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/CatalogueValidation.php';

// Vérifier la connexion

// Autoriser admin / employé
requireAdminOrEmployee();

// Vérifier l'identifiant
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit('ID de menu invalide.');
}

$id = (int) $id;

// Récupérer le menu
$sql = '
    SELECT *
    FROM menus
    WHERE id = :id
';

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$menu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    exit('Menu introuvable.');
}

$erreur = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $prix = (float) ($_POST['prix'] ?? 0);

    $nbPersonnesMin = (int) (
        $_POST['nb_personnes_min'] ?? 0
    );

    $theme = trim($_POST['theme'] ?? '');
    $regime = trim($_POST['regime'] ?? '');

    $stockDisponible = (int) (
        $_POST['stock_disponible'] ?? 0
    );

    $conditionsMenu = trim(
        $_POST['conditions_menu'] ?? ''
    );

    $delaiCommandeHeures = (int) (
        $_POST['delai_commande_heures'] ?? 0
    );

    $actif = isset($_POST['actif'])
        ? 1
        : 0;

    // Validation
    if (
        $titre === ''
        || $description === ''
        || $theme === ''
        || $regime === ''
    ) {
        $erreur =
            'Tous les champs obligatoires doivent être renseignés.';
    } elseif ($prix <= 0) {
        $erreur =
            'Le prix doit être supérieur à 0.';
    } elseif ($nbPersonnesMin < 1) {
        $erreur =
            'Le nombre minimum de personnes doit être au moins égal à 1.';
    } elseif ($stockDisponible < 0) {
        $erreur =
            'Le stock ne peut pas être négatif.';
    } elseif ($delaiCommandeHeures < 0) {
        $erreur =
            'Le délai de commande ne peut pas être négatif.';
    }

    // Mise à jour
    $erreur = menuValidationError($_POST) ?? $erreur;

    if ($erreur === '') {
        $sqlUpdate = '
            UPDATE menus

            SET
                titre = :titre,
                description = :description,
                prix = :prix,
                nb_personnes_min = :nb_personnes_min,
                theme = :theme,
                regime = :regime,
                stock_disponible = :stock_disponible,
                conditions_menu = :conditions_menu,
                delai_commande_heures = :delai_commande_heures,
                actif = :actif

            WHERE id = :id
        ';

        $stmtUpdate = $pdo->prepare(
            $sqlUpdate
        );

        $stmtUpdate->execute([
            ':titre' => $titre,
            ':description' => $description,
            ':prix' => $prix,
            ':nb_personnes_min' => $nbPersonnesMin,
            ':theme' => $theme,
            ':regime' => $regime,
            ':stock_disponible' => $stockDisponible,
            ':conditions_menu' => $conditionsMenu,
            ':delai_commande_heures' =>
                $delaiCommandeHeures,
            ':actif' => $actif,
            ':id' => $id
        ]);

        header(
            'Location: admin-menus.php'
        );

        exit;
    }

    // Réafficher les nouvelles valeurs si erreur
    $menu['titre'] = $titre;
    $menu['description'] = $description;
    $menu['prix'] = $prix;
    $menu['nb_personnes_min'] = $nbPersonnesMin;
    $menu['theme'] = $theme;
    $menu['regime'] = $regime;
    $menu['stock_disponible'] = $stockDisponible;
    $menu['conditions_menu'] = $conditionsMenu;
    $menu['delai_commande_heures'] = $delaiCommandeHeures;
    $menu['actif'] = $actif;
}

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Modifier un menu'); ?>
<section class="content-panel">
<p><a class="button button-secondary" href="gerer-menu-images.php?id=<?= (int) $id ?>">Gérer les images de ce menu</a></p>
    <h1>
        Modifier le menu
    </h1>
    <?php if ($erreur !== ''): ?>
        <p class="erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>
    <form method="POST">
        <?= csrfInput() ?>
        <label for="titre">Titre <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="titre"
            name="titre"
            value="<?= htmlspecialchars($menu['titre']) ?>"
            required
        >
        <label for="description">Description <span class="required-label">(obligatoire)</span></label>
        <textarea
            id="description"
            name="description"
            required
        ><?= htmlspecialchars($menu['description']) ?></textarea>
        <label for="prix">Prix (€) <span class="required-label">(obligatoire)</span></label>
        <input
            type="number"
            id="prix"
            name="prix"
            step="0.01"
            min="0.01"
            value="<?= htmlspecialchars($menu['prix']) ?>"
            required
        >
        <label for="nb_personnes_min">Nombre minimum de personnes <span class="required-label">(obligatoire)</span></label>
        <input
            type="number"
            id="nb_personnes_min"
            name="nb_personnes_min"
            min="1"
            value="<?= (int) $menu['nb_personnes_min'] ?>"
            required
        >
        <label for="theme">Thème <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="theme"
            name="theme"
            value="<?= htmlspecialchars($menu['theme']) ?>"
            required
        >
        <label for="regime">Régime alimentaire <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="regime"
            name="regime"
            value="<?= htmlspecialchars($menu['regime']) ?>"
            required
        >
        <label for="stock_disponible">Stock disponible <span class="required-label">(obligatoire)</span></label>
        <input
            type="number"
            id="stock_disponible"
            name="stock_disponible"
            min="0"
            value="<?= (int) $menu['stock_disponible'] ?>"
            required
        >
        <label for="conditions_menu">
            Conditions particulières
        </label>
        <textarea
            id="conditions_menu"
            name="conditions_menu"
        ><?= htmlspecialchars($menu['conditions_menu'] ?? '') ?></textarea>
        <label for="delai_commande_heures">Délai minimum de commande en heures <span class="required-label">(obligatoire)</span></label>
        <input
            type="number"
            id="delai_commande_heures"
            name="delai_commande_heures"
            min="0"
            value="<?= (int) $menu['delai_commande_heures'] ?>"
            required
        >
        <div class="checkbox">
            <input
                type="checkbox"
                id="actif"
                name="actif"
                <?php
    if ((int) $menu['actif'] === 1) {
        echo 'checked';
    }
?>
            >
            <label for="actif">
                Menu actif
            </label>
        </div>
        <button type="submit">
            Enregistrer les modifications
        </button>
    </form>
    <a
        class="retour"
        href="admin-menus.php"
    >
        Retour aux menus
    </a>
</section>
<?php renderFooter($pdo); ?>
