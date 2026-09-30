<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/OrderService.php';
requireLogin();
$id = positiveId($_GET['id'] ?? null);
$userId = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare('SELECT c.*, m.titre, m.nb_personnes_min FROM commandes c JOIN menus m ON m.id = c.menu_id WHERE c.id = ? AND c.user_id = ?');
$stmt->execute([$id, $userId]);
$commande = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$commande) {
    abortRequest(404, 'Commande introuvable.');
}
if ($commande['statut'] !== 'en attente') {
    abortRequest(409, 'Cette commande a été prise en charge et ne peut plus être modifiée.');
}
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        (new OrderService($pdo))->update($id, $userId, $_POST);
        header('Location: mes-commandes.php', true, 303);
        exit;
    } catch (DomainException $error) {
        $message = $error->getMessage();
        foreach (['nb_personnes', 'date_prestation', 'heure_prestation', 'lieu_prestation', 'adresse_prestation', 'distance_km'] as $field) {
            $commande[$field] = $_POST[$field] ?? '';
        }
    }
}
$commande['heure_prestation'] = substr($commande['heure_prestation'], 0, 5);
?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Modifier ma commande'); ?>
<section class="content-panel">
    <h1>
        Modifier ma commande
    </h1>
    <h2>
        <?= htmlspecialchars($commande['titre']) ?>
    </h2>
    <p>
        Le menu ne peut pas être modifié.
    </p>
    <?php if ($message): ?><p class="erreur" role="alert"><?= e($message) ?></p><?php endif; ?>
    <form method="POST">
        <?= csrfInput() ?>
        <label for="nb_personnes">
            Nombre de personnes
        </label>
        <input
            type="number"
            id="nb_personnes"
            name="nb_personnes"
            min="<?= (int) $commande['nb_personnes_min'] ?>"
            value="<?= (int) $commande['nb_personnes'] ?>"
            required
        >
        <label for="date_prestation">
            Date de prestation
        </label>
        <input
            type="date"
            id="date_prestation"
            name="date_prestation"
            value="<?= htmlspecialchars($commande['date_prestation'] ?? '') ?>"
            required
        >
        <label for="heure_prestation">
            Heure de prestation
        </label>
        <input
            type="time"
            id="heure_prestation"
            name="heure_prestation"
            value="<?= htmlspecialchars($commande['heure_prestation'] ?? '') ?>"
            required
        >
        <label for="lieu_prestation">
            Lieu
        </label>
        <input
            type="text"
            id="lieu_prestation"
            name="lieu_prestation"
            value="<?= htmlspecialchars($commande['lieu_prestation'] ?? '') ?>"
            required
        >
        <label for="adresse_prestation">
            Adresse
        </label>
        <input
            type="text"
            id="adresse_prestation"
            name="adresse_prestation"
            value="<?= htmlspecialchars($commande['adresse_prestation'] ?? '') ?>"
            required
        >
        <label for="distance_km">
            Distance hors Bordeaux en kilomètres
        </label>
        <input
            type="number"
            id="distance_km"
            name="distance_km"
            min="0"
            step="0.1"
            value="<?= htmlspecialchars($commande['distance_km'] ?? 0) ?>"
        >
        <button type="submit">
            Enregistrer les modifications
        </button>
    </form>
    <a
        class="retour"
        href="mes-commandes.php"
    >
        Retour à mes commandes
    </a>
</section>
<?php renderFooter($pdo); ?>
