<?php

require_once __DIR__ . '/../Config/database.php';

// Vérifier la connexion
requireLogin();

// Autoriser admin / employé
requireAdminOrEmployee();

// Vérifier l'identifiant
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit("ID de menu invalide.");
}

$id = (int) $id;

// Récupérer le menu
$sqlMenu = "
    SELECT *
    FROM menus
    WHERE id = :id
";

$stmtMenu = $pdo->prepare($sqlMenu);

$stmtMenu->execute([
    ':id' => $id
]);

$menu = $stmtMenu->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    exit("Menu introuvable.");
}

// Vérifier si des commandes utilisent ce menu
$sqlCommandes = "
    SELECT COUNT(*)
    FROM commandes
    WHERE menu_id = :menu_id
";

$stmtCommandes = $pdo->prepare(
    $sqlCommandes
);

$stmtCommandes->execute([
    ':menu_id' => $id
]);

$nombreCommandes = (int)
    $stmtCommandes->fetchColumn();

// Confirmation de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT id FROM menus WHERE id = ? FOR UPDATE');
        $lock->execute([$id]);
        $count = $pdo->prepare('SELECT COUNT(*) FROM commandes WHERE menu_id = ?');
        $count->execute([$id]);
        $nombreCommandes = (int) $count->fetchColumn();

        /*
         * Si le menu est déjà utilisé dans une commande,
         * on le désactive au lieu de le supprimer.
         *
         * Cela permet de conserver l'historique
         * des anciennes commandes.
         */
        if ($nombreCommandes > 0) {

            $sql = "
                UPDATE menus
                SET actif = 0
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':id' => $id
            ]);

        } else {

            // Si aucune commande ne l'utilise,
            // suppression réelle possible.
            $sql = "
                DELETE FROM menus
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':id' => $id
            ]);
        }

        $pdo->commit();
        header(
            "Location: admin-menus.php"
        );

        exit;

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }

        error_log(
            "Erreur suppression menu : "
            . $e->getMessage()
        );

        exit(
            "Une erreur est survenue lors de la suppression du menu."
        );
    }
}

?>

<?php require_once __DIR__ . '/../Templates/layout.php'; renderHeader('Supprimer un menu'); ?>

<section class="content-panel">

    <h1>
        Supprimer le menu
    </h1>

    <h2>

        <?php
        echo htmlspecialchars(
            $menu['titre']
        );
        ?>

    </h2>

    <?php if ($nombreCommandes > 0): ?>

        <div class="alerte">

            <strong>
                Ce menu est lié à
                <?php echo $nombreCommandes; ?>
                commande(s).
            </strong>

            <p>
                Il ne sera pas supprimé définitivement :
                il sera rendu inactif afin de conserver
                l'historique des commandes.
            </p>

        </div>

    <?php else: ?>

        <div class="alerte">

            <p>
                Ce menu n'est lié à aucune commande.
                Il peut être supprimé définitivement.
            </p>

        </div>

    <?php endif; ?>

    <form method="POST">
        <?= csrfInput() ?>

        <button type="submit">
            Confirmer la suppression
        </button>

        <a
            class="retour"
            href="admin-menus.php"
        >
            Annuler
        </a>

    </form>

</section>

<?php renderFooter($pdo); ?>
