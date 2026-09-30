<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/OrderService.php';
require_once __DIR__ . '/../Services/OrderNotifications.php';
requireAdminOrEmployee();
$id = positiveId($_GET['id'] ?? null);
$stmt = $pdo->prepare('SELECT c.*, m.titre, u.nom, u.prenom, u.email FROM commandes c JOIN menus m ON m.id = c.menu_id JOIN users u ON u.id = c.user_id WHERE c.id = ?');
$stmt->execute([$id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$commande) {
    abortRequest(404, 'Commande introuvable.');
}
if ($commande['statut'] !== 'en attente du retour de matériel' || (int) $commande['materiel_retourne'] || !$commande['date_debut_attente_retour']) {
    abortRequest(409, 'Cette commande n’attend pas de retour de matériel.');
}
$joursOuvres = calculerJoursOuvres($commande['date_debut_attente_retour'], date('Y-m-d H:i:s'));
$fraisRetard = $joursOuvres > 10 ? 600 : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        (new OrderService($pdo))->returnEquipment($id, (int) $_SESSION['user_id']);
        notifyOrderStatus($pdo, $id, 'terminée');
        header('Location: admin-commandes.php', true, 303);
        exit;
    } catch (DomainException $error) {
        abortRequest(409, $error->getMessage());
    }
}
?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Retour du matériel'); ?>
<section class="content-panel">
    <h1>
        Retour du matériel
    </h1>
    <div class="infos">
        <h2>
            Commande n°
            <?= (int) $commande['id'] ?>
        </h2>
        <p>
            <strong>
                Menu :
            </strong>
            <?= htmlspecialchars($commande['titre']) ?>
        </p>
        <p>
            <strong>
                Client :
            </strong>
            <?= htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']) ?>
        </p>
        <p>
            <strong>
                Email :
            </strong>
            <?= htmlspecialchars($commande['email']) ?>
        </p>
        <p>
            <strong>
                Début de l'attente du matériel :
            </strong>
            <?= htmlspecialchars($commande[ 'date_debut_attente_retour' ]) ?>
        </p>
        <p>
            <strong>
                Nombre de jours ouvrés écoulés :
            </strong>
            <?= $joursOuvres ?>
        </p>
    </div>
    <?php if ($fraisRetard > 0): ?>
        <div class="alerte">
            <strong>
                Délai de 10 jours ouvrés dépassé.
            </strong>
            <p>
                Frais de retard :
                600 €
            </p>
        </div>
    <?php else: ?>
        <div class="ok">

            Le matériel est retourné
            dans le délai prévu.

        </div>
    <?php endif; ?>
    <form method="POST">
        <?= csrfInput() ?>
        <button type="submit">
            Confirmer le retour du matériel
        </button>
    </form>
    <a
        class="retour"
        href="admin-commandes.php"
    >
        Retour aux commandes
    </a>
</section>
<?php renderFooter($pdo); ?>
