<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/OrderService.php';
require_once __DIR__ . '/../Services/OrderNotifications.php';
requireLogin();
requireFormMethod();
require_once __DIR__ . '/../Services/FormValidation.php';
$id = positiveId($_GET['id'] ?? null);
$stmt = $pdo->prepare('SELECT * FROM menus WHERE id = ?');
$stmt->execute([$id]);
$menu = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$menu || !(int) $menu['actif'] || (int) $menu['stock_disponible'] <= 0) {
    abortRequest(409, 'Ce menu est indisponible ou épuisé.');
}
$stmt = $pdo->prepare('SELECT nom, prenom, email, gsm, adresse FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$orderInput = ['adresse_prestation' => $user['adresse'], 'nb_personnes' => $menu['nb_personnes_min'], 'distance_km' => 0];
$adressePrestation = $user['adresse'];
$datePrestation = $heurePrestation = $lieuPrestation = $erreur = '';
$nbPersonnes = (int) $menu['nb_personnes_min'];
$distanceKm = $prixRepas = $remisePourcentage = $montantRemise = $fraisLivraison = $prixTotal = 0;
$recap = false;
$quoteToken = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? 'recap';
        $input = $_POST;
        if ($action === 'confirm') {
            // Reprendre le récapitulatif serveur, pas les prix ou coordonnées renvoyés par le client.
            $quoteToken = $_POST['quote_token'] ?? '';
            $quote = $_SESSION['order_quotes'][$quoteToken] ?? null;
            if (!$quote || $quote['menu_id'] !== $id || $quote['expires'] < time()) {
                throw new DomainException('Ce récapitulatif a expiré ou a déjà été confirmé. Recommencez la commande.');
            }
            $input = $quote['input'];
        }
        foreach (['adresse_prestation', 'lieu_prestation', 'date_prestation', 'heure_prestation', 'nb_personnes', 'distance_km'] as $field) {
            $orderInput[$field] = inputText($input, $field);
        }
        $data = validateOrderInput($input, $menu);
        $orderInput = $data;
        $adressePrestation = $data['adresse_prestation'];
        $datePrestation = $data['date_prestation'];
        $heurePrestation = $data['heure_prestation'];
        $lieuPrestation = $data['lieu_prestation'];
        $nbPersonnes = $data['nb_personnes'];
        $distanceKm = $data['distance_km'];
        $prixRepas = $data['prix_repas'];
        $remisePourcentage = $data['remise_pourcentage'];
        $montantRemise = $data['montant_remise'];
        $fraisLivraison = $data['frais_livraison'];
        $prixTotal = $data['prix_total'];
        if ($action === 'recap') {
            $recap = true;
            $quoteToken = bin2hex(random_bytes(24));
            $input['_quoted_total'] = $prixTotal;
            $_SESSION['order_quotes'][$quoteToken] = ['menu_id' => $id, 'input' => $input, 'expires' => time() + 1800];
            $_SESSION['order_quotes'] = array_slice($_SESSION['order_quotes'], -10, null, true);
        } elseif ($action === 'confirm') {
            $orderId = (new OrderService($pdo))->create((int) $_SESSION['user_id'], $id, $input);
            // Consommer le jeton après validation SQL ; l'email ne doit pas annuler la commande.
            unset($_SESSION['order_quotes'][$quoteToken]);
            notifyOrderStatus($pdo, $orderId, 'en attente');
            header('Location: mes-commandes.php', true, 303);
            exit;
        } else {
            throw new DomainException('Action invalide.');
        }
    } catch (DomainException $error) {
        $erreur = $error->getMessage();
    }
}
?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Commander'); ?>
<section class="content-panel">
    <h1><?= e($menu['titre']) ?></h1>
    <p><?= e($menu['description']) ?></p>
    <dl class="infos-menu">
        <dt>Prix de base</dt><dd><?= number_format((float) $menu['prix'], 2, ',', ' ') ?> € pour <?= (int) $menu['nb_personnes_min'] ?> personnes</dd>
        <dt>Commandes encore disponibles</dt><dd><?= (int) $menu['stock_disponible'] ?></dd>
        <dt>Délai minimum de commande</dt><dd><?= (int) $menu['delai_commande_heures'] ?> heure(s)</dd>
    </dl>
    <?php if ($erreur !== ''): ?><p class="erreur" role="alert"><?= e($erreur) ?></p><?php endif; ?>
    <form method="post">
        <?= csrfInput() ?>
        <h2>Votre commande</h2>
        <dl>
            <dt>Client</dt><dd><?= e($user['prenom'] . ' ' . $user['nom']) ?></dd>
            <dt>Adresse email</dt><dd><?= e($user['email']) ?></dd>
            <dt>Téléphone</dt><dd><?= e($user['gsm']) ?></dd>
        </dl>
        <p class="small-note">Pour corriger vos coordonnées, rendez-vous dans <a href="mon-profil.php">Mon profil</a>.</p>
        <?php $minimumPeople = (int) $menu['nb_personnes_min'];
require __DIR__ . '/../Templates/forms/order-fields.php'; ?>
        <button type="submit" name="action" value="recap">Voir le récapitulatif</button>
    </form>
    <?php if ($recap): ?>
        <section class="recap" aria-labelledby="recap-title">
            <h2 id="recap-title" tabindex="-1">Récapitulatif de votre commande</h2>
            <dl>
                <dt>Menu</dt><dd><?= e($menu['titre']) ?></dd>
                <dt>Date et heure</dt><dd><?= e($datePrestation . ' à ' . $heurePrestation) ?></dd>
                <dt>Adresse</dt><dd><?= e($adressePrestation . ', ' . $lieuPrestation) ?></dd>
                <dt>Nombre de personnes</dt><dd><?= (int) $nbPersonnes ?></dd>
                <dt>Prix du repas</dt><dd><?= number_format($prixRepas, 2, ',', ' ') ?> €</dd>
                <dt>Remise de <?= (int) $remisePourcentage ?> %</dt><dd><?= number_format($montantRemise, 2, ',', ' ') ?> €</dd>
                <dt>Livraison</dt><dd><?= number_format($fraisLivraison, 2, ',', ' ') ?> €</dd>
                <dt>Total</dt><dd><strong><?= number_format($prixTotal, 2, ',', ' ') ?> €</strong></dd>
            </dl>
            <form method="post">
                <?= csrfInput() ?>
                <!-- Seul le jeton est transmis : les données du devis restent en session. -->
                <input type="hidden" name="quote_token" value="<?= e($quoteToken) ?>">
                <button type="submit" name="action" value="confirm">Confirmer la commande</button>
            </form>
        </section>
    <?php endif; ?>
</section>
<?php renderFooter($pdo); ?>
