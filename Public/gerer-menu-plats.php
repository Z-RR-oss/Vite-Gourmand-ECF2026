<?php

require_once __DIR__ . '/../Config/database.php';

requireAdminOrEmployee();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit('ID de menu invalide.');
}

$id = (int) $id;

$sqlMenu = '
    SELECT *
    FROM menus
    WHERE id = :id
';

$stmtMenu = $pdo->prepare($sqlMenu);

$stmtMenu->execute([
    ':id' => $id
]);

$menu = $stmtMenu->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    exit('Menu introuvable.');
}

$sqlPlats = "
    SELECT
        id,
        nom,
        description,
        type_plat
    FROM plats

    ORDER BY
        FIELD(
            type_plat,
            'entree',
            'plat',
            'dessert'
        ),
        nom ASC
";

$stmtPlats = $pdo->prepare($sqlPlats);
$stmtPlats->execute();

$plats = $stmtPlats->fetchAll(PDO::FETCH_ASSOC);

$sqlPlatsMenu = '
    SELECT
        plat_id,
        ordre_affichage
    FROM menu_plat
    WHERE menu_id = :menu_id
';

$stmtPlatsMenu = $pdo->prepare(
    $sqlPlatsMenu
);

$stmtPlatsMenu->execute([
    ':menu_id' => $id
]);

$platsMenu = $stmtPlatsMenu->fetchAll(
    PDO::FETCH_ASSOC
);

// Tableau pratique :
// [plat_id => ordre_affichage]
$platsSelectionnes = [];

foreach ($platsMenu as $platMenu) {
    $platsSelectionnes[
        $platMenu['plat_id']
    ] = $platMenu['ordre_affichage'];
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $platsChoisis =
        $_POST['plats'] ?? [];

    $ordres =
        $_POST['ordre'] ?? [];

    try {
        if (!is_array($platsChoisis) || !is_array($ordres) || array_diff($platsChoisis, array_column($plats, 'id'))) {
            throw new DomainException('Sélection de plats invalide.');
        }
        $platsChoisis = array_unique($platsChoisis);
        foreach ($ordres as $value) {
            if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000]]) === false) {
                throw new DomainException('Ordre d’affichage invalide.');
            }
        }
        $pdo->beginTransaction();

        $sqlDelete = '
            DELETE FROM menu_plat
            WHERE menu_id = :menu_id
        ';

        $stmtDelete = $pdo->prepare(
            $sqlDelete
        );

        $stmtDelete->execute([
            ':menu_id' => $id
        ]);

        foreach ($platsChoisis as $platId) {
            if (!is_numeric($platId)) {
                continue;
            }

            $platId = (int) $platId;

            $ordre = isset($ordres[$platId])
                ? (int) $ordres[$platId]
                : 0;

            if ($ordre < 0) {
                $ordre = 0;
            }

            $sqlInsert = '
                INSERT INTO menu_plat (
                    menu_id,
                    plat_id,
                    ordre_affichage
                )

                VALUES (
                    :menu_id,
                    :plat_id,
                    :ordre_affichage
                )
            ';

            $stmtInsert = $pdo->prepare(
                $sqlInsert
            );

            $stmtInsert->execute([
                ':menu_id' => $id,
                ':plat_id' => $platId,
                ':ordre_affichage' => $ordre
            ]);
        }

        $pdo->commit();

        header(
            'Location: admin-menus.php'
        );

        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Erreur association menu/plats : '
            . $e->getMessage()
        );

        $erreur =
            "Une erreur est survenue lors de l'enregistrement.";
    }
}

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Composition du menu'); ?>
<section class="content-panel">
    <h1>
        Composition du menu
    </h1>
    <h2>
        <?= htmlspecialchars($menu['titre']) ?>
    </h2>
    <p>
        Sélectionne les plats qui composent ce menu.
        Le numéro d'ordre détermine leur ordre d'affichage.
    </p>
    <?php if ($erreur !== ''): ?>
        <p class="erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>
    <form method="POST">
        <?= csrfInput() ?>
        <?php if (empty($plats)): ?>
            <p>
                Aucun plat disponible.
                Crée d'abord des plats dans la gestion des plats.
            </p>
        <?php else: ?>
            <?php foreach ($plats as $plat): ?>
                <?php

            $platId = (int) $plat['id'];

                $estSelectionne =
                    array_key_exists(
                        $platId,
                        $platsSelectionnes
                    );

                $ordreActuel =
                    $platsSelectionnes[$platId]
                    ?? 0;

                ?>
                <div class="plat">
                    <div class="plat-infos">
                        <strong>
                            <?= htmlspecialchars($plat['nom']) ?>
                        </strong>
                        <p class="type">
                            <?php

                if ($plat['type_plat'] === 'entree') {
                    echo 'Entrée';
                } elseif ($plat['type_plat'] === 'dessert') {
                    echo 'Dessert';
                } else {
                    echo 'Plat';
                }

                ?>
                        </p>
                        <?php
                        if (!empty($plat['description'])):
                            ?>
                            <p>
                                <?= htmlspecialchars($plat['description']) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="selection">
                        <label>
                            <input
                                type="checkbox"
                                name="plats[]"
                                value="<?= $platId ?>"
                                <?php
                if ($estSelectionne) {
                    echo 'checked';
                }
                ?>
                            >

                            Sélectionner

                        </label>
                        <label>

                            Ordre :

                            <input
                                class="ordre"
                                type="number"
                                name="ordre[<?= $platId ?>]"
                                min="0"
                                value="<?= (int) $ordreActuel ?>"
                            >
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($plats)): ?>
            <button type="submit">
                Enregistrer la composition
            </button>
        <?php endif; ?>
    </form>
    <a
        class="retour"
        href="admin-menus.php"
    >
        Retour aux menus
    </a>
</section>
<?php renderFooter($pdo); ?>
