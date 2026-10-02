<?php

require_once __DIR__ . '/../Config/database.php';

requireAdminOrEmployee();
requireFormMethod();

// Vérifier l'identifiant
$id = positiveId($_GET['id'] ?? null);

// Récupérer le plat
$sql = '
    SELECT *
    FROM plats
    WHERE id = :id
';

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$plat = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plat) {
    abortRequest(404, 'Plat introuvable.');
}

// Vérifier combien de menus utilisent ce plat
$sqlMenus = '
    SELECT COUNT(*)
    FROM menu_plat
    WHERE plat_id = :plat_id
';

$stmtMenus = $pdo->prepare(
    $sqlMenus
);

$stmtMenus->execute([
    ':plat_id' => $id
]);

$nombreMenus = (int)
    $stmtMenus->fetchColumn();

// Suppression après confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();

        // Supprimer les liens avec les menus
        $sqlMenuPlat = '
            DELETE FROM menu_plat
            WHERE plat_id = :plat_id
        ';

        $stmtMenuPlat = $pdo->prepare(
            $sqlMenuPlat
        );

        $stmtMenuPlat->execute([
            ':plat_id' => $id
        ]);

        // Supprimer les liens avec les allergènes
        $sqlAllergenes = '
            DELETE FROM plat_allergene
            WHERE plat_id = :plat_id
        ';

        $stmtAllergenes = $pdo->prepare(
            $sqlAllergenes
        );

        $stmtAllergenes->execute([
            ':plat_id' => $id
        ]);

        // Supprimer le plat
        $sqlDelete = '
            DELETE FROM plats
            WHERE id = :id
        ';

        $stmtDelete = $pdo->prepare(
            $sqlDelete
        );

        $stmtDelete->execute([
            ':id' => $id
        ]);

        $pdo->commit();

        header('Location: admin-plats.php', true, 303);

        exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            'Erreur suppression plat : '
            . $e->getMessage()
        );

        abortRequest(
            500,
            'Une erreur est survenue lors de la suppression du plat.'
        );
    }
}

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Supprimer un plat'); ?>
<section class="content-panel">
    <h1>
        Supprimer le plat
    </h1>
    <h2>
        <?= htmlspecialchars($plat['nom']) ?>
    </h2>
    <div class="alerte">
        <?php if ($nombreMenus > 0): ?>
            <p>

                Ce plat appartient actuellement à

                <strong>
                    <?= $nombreMenus ?>
                    menu(s).
                </strong>
            </p>
            <p>
                Sa suppression le retirera également
                de ces menus.
            </p>
        <?php else: ?>
            <p>
                Ce plat n'est actuellement associé
                à aucun menu.
            </p>
        <?php endif; ?>
    </div>
    <form method="POST">
        <?= csrfInput() ?>
        <button type="submit">
            Confirmer la suppression
        </button>
        <a
            class="retour"
            href="admin-plats.php"
        >
            Annuler
        </a>
    </form>
</section>
<?php renderFooter($pdo); ?>
