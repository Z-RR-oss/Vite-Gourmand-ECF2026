<?php

require_once __DIR__ . '/../Config/database.php';

requireAdminOrEmployee();

$sql = '
    SELECT *
    FROM menus
    ORDER BY id DESC
';

$stmt = $pdo->prepare($sql);
$stmt->execute();

$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Gestion des menus'); ?>
<section class="content-panel">
    <h1>
        Gestion des menus
    </h1>
    <div class="header-actions">
        <a
            class="bouton"
            href="ajouter-menu.php"
        >
            + Ajouter un menu
        </a>
    </div>
    <?php if (empty($menus)): ?>
        <p>
            Aucun menu enregistré.
        </p>
    <?php else: ?>
        <?php foreach ($menus as $menu): ?>
            <article class="menu">
                <h2>
                    <?= htmlspecialchars($menu['titre']) ?>
                </h2>
                <p>
                    <?= nl2br(htmlspecialchars($menu['description'])) ?>
                </p>
                <p>
                    <strong>Prix :</strong>
                    <?= number_format($menu['prix'], 2, ',', ' ') ?>

                    €

                </p>
                <p>
                    <strong>
                        Nombre minimum de personnes :
                    </strong>
                    <?= (int) $menu['nb_personnes_min'] ?>
                </p>
                <p>
                    <strong>Thème :</strong>
                    <?= htmlspecialchars($menu['theme']) ?>
                </p>
                <p>
                    <strong>Régime :</strong>
                    <?= htmlspecialchars($menu['regime']) ?>
                </p>
                <p>
                    <strong>
                        Stock disponible :
                    </strong>
                    <?= (int) $menu['stock_disponible'] ?>
                </p>
                <p>
                    <strong>
                        Délai de commande :
                    </strong>
                    <?= (int) $menu['delai_commande_heures'] ?>

                    heure(s)

                </p>
                <p>
                    <strong>
                        Conditions :
                    </strong>
                    <?= !empty($menu['conditions_menu']) ? htmlspecialchars($menu['conditions_menu']) : 'Aucune' ?>
                </p>
                <p>
                    <?php if ((int) $menu['actif'] === 1): ?>
                        <span class="statut-actif">
                            Actif
                        </span>
                    <?php else: ?>
                        <span class="statut-inactif">
                            Inactif
                        </span>
                    <?php endif; ?>
                </p>
                <div class="actions">
                    <a
                        href="menu.php?id=<?= (int) $menu['id'] ?>"
                    >
                        Voir
                    </a>
                    <a
                        class="plats"
                        href="gerer-menu-plats.php?id=<?= (int) $menu['id'] ?>"
                    >
                        Gérer les plats
                    </a>
                    <a href="gerer-menu-images.php?id=<?= (int) $menu['id'] ?>">Images</a>
                    <a
                        href="modifier-menu.php?id=<?= (int) $menu['id'] ?>"
                    >
                        Modifier
                    </a>
                    <a
                        class="supprimer"
                        href="supprimer-menu.php?id=<?= (int) $menu['id'] ?>"
                    >
                        Supprimer
                    </a>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>
<?php renderFooter($pdo); ?>
