<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/OrderService.php';
requireAdminOrEmployee();
$id = positiveId($_GET['id'] ?? null);
$stmt = $pdo->prepare('SELECT c.*, m.titre, u.nom, u.prenom, u.email, u.gsm FROM commandes c JOIN menus m ON m.id = c.menu_id JOIN users u ON u.id = c.user_id WHERE c.id = ?');
$stmt->execute([$id]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$commande) {
    abortRequest(404, 'Commande introuvable.');
}
if (in_array($commande['statut'], ['annulée', 'terminée'], true)) {
    abortRequest(409, 'Cette commande est déjà clôturée.');
}
$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        (new OrderService($pdo))->cancel($id, (int) $_SESSION['user_id'], true, trim($_POST['mode_contact'] ?? ''), trim($_POST['motif'] ?? ''));
        header('Location: admin-commandes.php', true, 303);
        exit;
    } catch (DomainException $error) {
        $erreur = $error->getMessage();
    }
}
?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Annuler une commande'); ?>
<section class="content-panel">
    <h1>
        Annuler une commande
    </h1>
    <div class="commande-info">
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
                Téléphone :
            </strong>
            <?= htmlspecialchars($commande['gsm']) ?>
        </p>
        <p>
            <strong>
                Statut actuel :
            </strong>
            <?= htmlspecialchars($commande['statut']) ?>
        </p>
    </div>
    <?php if ($erreur !== ''): ?>
        <p class="erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>
    <p>
        Avant d'annuler la commande,
        le client doit être contacté.
    </p>
    <form method="POST">
        <?= csrfInput() ?>
        <label for="mode_contact">Mode de contact utilisé <span class="required-label">(obligatoire)</span></label>
        <select
            id="mode_contact"
            name="mode_contact"
            required
        >
            <option value="">
                Sélectionner
            </option>
            <option value="Téléphone">
                Téléphone
            </option>
            <option value="Email">
                Email
            </option>
            <option value="SMS">
                SMS
            </option>
        </select>
        <label for="motif">Motif de l'annulation <span class="required-label">(obligatoire)</span></label>
        <textarea
            id="motif"
            name="motif"
            required
            placeholder="Expliquez la raison de l'annulation..."
        ></textarea>
        <button type="submit">
            Confirmer l'annulation
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
