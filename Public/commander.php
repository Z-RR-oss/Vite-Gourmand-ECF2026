<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


// 2. Récupérer l'id du menu
$id = $_GET['id'] ?? null;

if (!$id) {
    exit("Menu introuvable.");
}


// 3. Récupérer le menu
$sql = "SELECT * FROM menus WHERE id = :id";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$menu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$menu) {
    exit("Menu introuvable.");
}


// 4. Récupérer l'utilisateur connecté
$sqlUser = "SELECT * FROM users WHERE id = :id";

$stmtUser = $pdo->prepare($sqlUser);

$stmtUser->execute([
    ':id' => $_SESSION['user_id']
]);

$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    exit("Utilisateur introuvable.");
}


// Valeurs par défaut du formulaire
$adressePrestation = $user['adresse'];
$datePrestation = '';
$heurePrestation = '';
$lieuPrestation = '';
$nbPersonnes = (int) $menu['nb_personnes_min'];
$distanceKm = 0;

$recap = false;


// 5. Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $adressePrestation = $_POST['adresse_prestation'] ?? '';
    $datePrestation = $_POST['date_prestation'] ?? '';
    $heurePrestation = $_POST['heure_prestation'] ?? '';
    $lieuPrestation = $_POST['lieu_prestation'] ?? '';

    $nbPersonnes = (int) ($_POST['nb_personnes'] ?? 0);
    $distanceKm = (float) ($_POST['distance_km'] ?? 0);

    $action = $_POST['action'] ?? 'recap';


    // Vérifier les champs obligatoires
    if (
        empty($adressePrestation) ||
        empty($datePrestation) ||
        empty($heurePrestation) ||
        empty($lieuPrestation)
    ) {
        exit("Tous les champs sont obligatoires.");
    }


    // Vérifier le minimum de personnes
    if ($nbPersonnes < $menu['nb_personnes_min']) {
        exit(
            "Le nombre de personnes doit être au minimum de "
            . $menu['nb_personnes_min']
            . "."
        );
    }


    // Vérifier la distance
    if ($distanceKm < 0) {
        exit("La distance ne peut pas être négative.");
    }


    // 6. Calcul du prix
    $prixParPersonne =
        $menu['prix'] / $menu['nb_personnes_min'];

    $prixRepas =
        $prixParPersonne * $nbPersonnes;


    // Remise
    $remisePourcentage = 0;

    if ($nbPersonnes >= $menu['nb_personnes_min'] + 5) {
        $remisePourcentage = 10;
    }

    $montantRemise =
        $prixRepas * ($remisePourcentage / 100);

    $prixApresRemise =
        $prixRepas - $montantRemise;


    // Livraison
    $fraisLivraison = 0;

    if ($distanceKm > 0) {
        $fraisLivraison =
            5 + (0.59 * $distanceKm);
    }


    // Total
    $prixTotal =
        $prixApresRemise + $fraisLivraison;

    $prixTotal = round($prixTotal, 2);
    $prixRepas = round($prixRepas, 2);
    $montantRemise = round($montantRemise, 2);
    $fraisLivraison = round($fraisLivraison, 2);


    // 7. Premier clic : afficher le récapitulatif
    if ($action === 'recap') {
        $recap = true;
    }


    // 8. Deuxième clic : confirmer et enregistrer
    if ($action === 'confirm') {

        $sqlCommande = "
            INSERT INTO commandes (
                user_id,
                menu_id,
                nb_personnes,
                prix_total,
                date_prestation,
                heure_prestation,
                lieu_prestation,
                adresse_prestation,
                distance_km,
                frais_livraison,
                remise_pourcentage
            )
            VALUES (
                :user_id,
                :menu_id,
                :nb_personnes,
                :prix_total,
                :date_prestation,
                :heure_prestation,
                :lieu_prestation,
                :adresse_prestation,
                :distance_km,
                :frais_livraison,
                :remise_pourcentage
            )
        ";

        $stmtCommande = $pdo->prepare($sqlCommande);

        $stmtCommande->execute([
            ':user_id' => $_SESSION['user_id'],
            ':menu_id' => $menu['id'],
            ':nb_personnes' => $nbPersonnes,
            ':prix_total' => $prixTotal,
            ':date_prestation' => $datePrestation,
            ':heure_prestation' => $heurePrestation,
            ':lieu_prestation' => $lieuPrestation,
            ':adresse_prestation' => $adressePrestation,
            ':distance_km' => $distanceKm,
            ':frais_livraison' => $fraisLivraison,
            ':remise_pourcentage' => $remisePourcentage
        ]);

        header("Location: mes-commandes.php");
        exit;
    }
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Commander - Vite & Gourmand</title>

</head>

<body>

    <h1>
        <?php echo htmlspecialchars($menu['titre']); ?>
    </h1>

    <p>
        <?php echo htmlspecialchars($menu['description']); ?>
    </p>

    <p>
        Prix de base :
        <?php echo number_format($menu['prix'], 2, ',', ' '); ?> €
    </p>

    <p>
        Minimum :
        <?php echo (int) $menu['nb_personnes_min']; ?>
        personnes
    </p>


    <form method="post">

        <h2>Votre commande</h2>


        <label for="nom">Nom</label>

        <input
            type="text"
            id="nom"
            value="<?php echo htmlspecialchars($user['nom']); ?>"
            disabled
        >


        <label for="prenom">Prénom</label>

        <input
            type="text"
            id="prenom"
            value="<?php echo htmlspecialchars($user['prenom']); ?>"
            disabled
        >


        <label for="adresse_prestation">
            Adresse de prestation
        </label>

        <input
            type="text"
            id="adresse_prestation"
            name="adresse_prestation"
            value="<?php echo htmlspecialchars($adressePrestation); ?>"
            required
        >


        <label for="date_prestation">
            Date de prestation
        </label>

        <input
            type="date"
            id="date_prestation"
            name="date_prestation"
            value="<?php echo htmlspecialchars($datePrestation); ?>"
            required
        >


        <label for="heure_prestation">
            Heure de prestation
        </label>

        <input
            type="time"
            id="heure_prestation"
            name="heure_prestation"
            value="<?php echo htmlspecialchars($heurePrestation); ?>"
            required
        >


        <label for="lieu_prestation">
            Lieu de prestation
        </label>

        <input
            type="text"
            id="lieu_prestation"
            name="lieu_prestation"
            value="<?php echo htmlspecialchars($lieuPrestation); ?>"
            required
        >


        <label for="nb_personnes">
            Nombre de personnes
        </label>

        <input
            type="number"
            id="nb_personnes"
            name="nb_personnes"
            min="<?php echo (int) $menu['nb_personnes_min']; ?>"
            value="<?php echo $nbPersonnes; ?>"
            required
        >


        <label for="distance_km">
            Distance hors Bordeaux en km
        </label>

        <input
            type="number"
            id="distance_km"
            name="distance_km"
            min="0"
            step="0.1"
            value="<?php echo $distanceKm; ?>"
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

        <hr>

        <h2>Récapitulatif de votre commande</h2>

        <p>
            Menu :
            <?php echo htmlspecialchars($menu['titre']); ?>
        </p>

        <p>
            Nombre de personnes :
            <?php echo $nbPersonnes; ?>
        </p>

        <p>
            Prix du repas :
            <?php echo number_format($prixRepas, 2, ',', ' '); ?> €
        </p>

        <p>
            Remise :
            <?php echo $remisePourcentage; ?> %
        </p>

        <p>
            Montant de la remise :
            <?php echo number_format($montantRemise, 2, ',', ' '); ?> €
        </p>

        <p>
            Frais de livraison :
            <?php echo number_format($fraisLivraison, 2, ',', ' '); ?> €
        </p>

        <h3>
            Total :
            <?php echo number_format($prixTotal, 2, ',', ' '); ?> €
        </h3>


        <form method="post">

            <input
                type="hidden"
                name="adresse_prestation"
                value="<?php echo htmlspecialchars($adressePrestation); ?>"
            >

            <input
                type="hidden"
                name="date_prestation"
                value="<?php echo htmlspecialchars($datePrestation); ?>"
            >

            <input
                type="hidden"
                name="heure_prestation"
                value="<?php echo htmlspecialchars($heurePrestation); ?>"
            >

            <input
                type="hidden"
                name="lieu_prestation"
                value="<?php echo htmlspecialchars($lieuPrestation); ?>"
            >

            <input
                type="hidden"
                name="nb_personnes"
                value="<?php echo $nbPersonnes; ?>"
            >

            <input
                type="hidden"
                name="distance_km"
                value="<?php echo $distanceKm; ?>"
            >

            <button
                type="submit"
                name="action"
                value="confirm"
            >
                Confirmer la commande
            </button>

        </form>

    <?php endif; ?>

</body>

</html>