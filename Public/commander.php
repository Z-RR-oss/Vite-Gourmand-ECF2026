<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/OrderService.php';
require_once __DIR__ . '/../Services/OrderNotifications.php';
requireLogin();
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
        $data = validateOrderInput($input, $menu);
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
    <h1>
        <?= htmlspecialchars($menu['titre']) ?>
    </h1>
    <p>
        <?= htmlspecialchars($menu['description']) ?>
    </p>
    <div class="infos-menu">
        <p>
            <strong>
                Prix de base :
            </strong>
            <?= number_format($menu['prix'], 2, ',', ' ') ?>

            €

        </p>
        <p>
            <strong>
                Minimum :
            </strong>
            <?= (int) $menu['nb_personnes_min'] ?>

            personnes

        </p>
        <p class="stock">

            Commandes encore disponibles :

            <?= (int) $menu['stock_disponible'] ?>
        </p>
        <p>

            Délai minimum de commande :

            <?= (int) $menu[ 'delai_commande_heures' ] ?>

            heure(s)

        </p>
    </div>
    <?php if ($erreur !== ''): ?>
        <p class="erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>
    <form method="post">
        <?= csrfInput() ?>
        <input type="hidden" name="quote_token" value="<?= e($quoteToken) ?>">
        <h2>
            Votre commande
        </h2>
        <label for="nom">
            Nom
        </label>
        <input
            type="text"
            id="nom"
            value="<?= htmlspecialchars($user['nom']) ?>"
            disabled
        >
        <label for="prenom">
            Prénom
        </label>
        <input
            type="text"
            id="prenom"
            value="<?= htmlspecialchars($user['prenom']) ?>"
            disabled
        >
        <label for="client-email">Adresse email du compte</label>
        <input id="client-email" type="email" value="<?= e($user['email']) ?>" disabled>
        <label for="client-gsm">Téléphone du compte</label>
        <input id="client-gsm" type="tel" value="<?= e($user['gsm']) ?>" disabled>
        <p class="small-note">Pour les corriger, rendez-vous dans <a href="mon-profil.php">Mon profil</a>.</p>
        <label for="adresse_prestation">Adresse de prestation <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="adresse_prestation"
            name="adresse_prestation"
            value="<?= htmlspecialchars($adressePrestation) ?>"
            required
        >
        <label for="date_prestation">Date de prestation <span class="required-label">(obligatoire)</span></label>
        <input
            type="date"
            id="date_prestation"
            name="date_prestation"
            value="<?= htmlspecialchars($datePrestation) ?>"
            required
        >
        <label for="heure_prestation">Heure de prestation <span class="required-label">(obligatoire)</span></label>
        <input
            type="time"
            id="heure_prestation"
            name="heure_prestation"
            value="<?= htmlspecialchars($heurePrestation) ?>"
            required
        >
        <label for="lieu_prestation">Lieu de prestation <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="lieu_prestation"
            name="lieu_prestation"
            value="<?= htmlspecialchars($lieuPrestation) ?>"
            required
        >
        <label for="nb_personnes">Nombre de personnes <span class="required-label">(obligatoire)</span></label>
        <input
            type="number"
            id="nb_personnes"
            name="nb_personnes"
            min="<?= (int) $menu['nb_personnes_min'] ?>"
            value="<?= $nbPersonnes ?>"
            required
        >
        <label for="distance_km">Distance hors Bordeaux en km <span class="required-label">(obligatoire)</span></label>
        <input
            type="number"
            id="distance_km"
            name="distance_km"
            min="0"
            step="0.1"
            value="<?= $distanceKm ?>"
            required
        >
        <button
            type="submit"
            name="action"
            value="recap"
        >
            Voir le récapitulatif
        </button>
    </form>
    <?php if ($recap): ?>
        <section class="recap">
            <h2>
                Récapitulatif de votre commande
            </h2>
            <p>

                Menu :

                <strong>
                    <?= htmlspecialchars($menu['titre']) ?>
                </strong>
            </p>
            <p>

                Date :

                <?= htmlspecialchars($datePrestation) ?>

                à

                <?= htmlspecialchars($heurePrestation) ?>
            </p>
            <p>

                Nombre de personnes :

                <?= $nbPersonnes ?>
            </p>
            <p>

                Prix du repas :

                <?= number_format($prixRepas, 2, ',', ' ') ?>

                €

            </p>
            <p>

                Remise :

                <?= $remisePourcentage ?>

                %

            </p>
            <p>

                Montant de la remise :

                <?= number_format($montantRemise, 2, ',', ' ') ?>

                €

            </p>
            <p>

                Frais de livraison :

                <?= number_format($fraisLivraison, 2, ',', ' ') ?>

                €

            </p>
            <h3>

                Total :

                <?= number_format($prixTotal, 2, ',', ' ') ?>

                €

            </h3>
            <form method="post">
        <?= csrfInput() ?>
        <input type="hidden" name="quote_token" value="<?= e($quoteToken) ?>">
                <input
                    type="hidden"
                    name="adresse_prestation"
                    value="<?= htmlspecialchars($adressePrestation) ?>"
                >
                <input
                    type="hidden"
                    name="date_prestation"
                    value="<?= htmlspecialchars($datePrestation) ?>"
                >
                <input
                    type="hidden"
                    name="heure_prestation"
                    value="<?= htmlspecialchars($heurePrestation) ?>"
                >
                <input
                    type="hidden"
                    name="lieu_prestation"
                    value="<?= htmlspecialchars($lieuPrestation) ?>"
                >
                <input
                    type="hidden"
                    name="nb_personnes"
                    value="<?= $nbPersonnes ?>"
                >
                <input
                    type="hidden"
                    name="distance_km"
                    value="<?= $distanceKm ?>"
                >
                <button
                    type="submit"
                    name="action"
                    value="confirm"
                >
                    Confirmer la commande
                </button>
            </form>
        </section>
    <?php endif; ?>
</section>
<?php renderFooter($pdo); ?>
