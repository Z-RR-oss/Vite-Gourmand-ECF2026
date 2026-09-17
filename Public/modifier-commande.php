<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];


// 2. Vérifier l'identifiant de la commande
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit("ID de commande invalide.");
}

$id = (int) $id;


// 3. Récupérer uniquement une commande appartenant à l'utilisateur
$sql = "
    SELECT
        commandes.*,
        menus.titre,
        menus.prix AS prix_menu,
        menus.nb_personnes_min
    FROM commandes

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    WHERE commandes.id = :id
    AND commandes.user_id = :user_id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id,
    ':user_id' => $user_id
]);

$commande = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$commande) {
    exit("Commande introuvable ou accès refusé.");
}


// 4. Une commande acceptée ne peut plus être modifiée
if ($commande['statut'] !== 'en attente') {
    exit(
        "Cette commande ne peut plus être modifiée car elle a déjà été prise en charge."
    );
}


// Message affiché après modification
$message = '';


// 5. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nb_personnes = (int) ($_POST['nb_personnes'] ?? 0);

    $date_prestation = trim(
        $_POST['date_prestation'] ?? ''
    );

    $heure_prestation = trim(
        $_POST['heure_prestation'] ?? ''
    );

    $lieu_prestation = trim(
        $_POST['lieu_prestation'] ?? ''
    );

    $adresse_prestation = trim(
        $_POST['adresse_prestation'] ?? ''
    );

    $distance_km = (float) (
        $_POST['distance_km'] ?? 0
    );


    // 6. Vérifications
    if (
        $date_prestation === ''
        || $heure_prestation === ''
        || $lieu_prestation === ''
        || $adresse_prestation === ''
    ) {
        exit("Tous les champs obligatoires doivent être renseignés.");
    }


    if ($nb_personnes < (int) $commande['nb_personnes_min']) {

        exit(
            "Le nombre de personnes doit être au minimum de "
            . (int) $commande['nb_personnes_min']
            . "."
        );
    }


    if ($distance_km < 0) {
        exit("La distance ne peut pas être négative.");
    }


    // 7. Recalcul du prix

    $prix_menu = (float) $commande['prix_menu'];

    $minimum = (int) $commande['nb_personnes_min'];


    // Prix par personne
    $prix_par_personne =
        $prix_menu / $minimum;


    // Prix selon le nombre de personnes
    $prix_repas =
        $prix_par_personne * $nb_personnes;


    // Remise de 10 % si minimum + 5 personnes
    $remise_pourcentage = 0;

    if (
        $nb_personnes
        >= $minimum + 5
    ) {
        $remise_pourcentage = 10;
    }


    $montant_remise =
        $prix_repas
        * ($remise_pourcentage / 100);


    $prix_apres_remise =
        $prix_repas
        - $montant_remise;


    // Livraison hors Bordeaux
    $frais_livraison = 0;

    if ($distance_km > 0) {

        $frais_livraison =
            5
            + (0.59 * $distance_km);
    }


    // Prix total
    $prix_total =
        $prix_apres_remise
        + $frais_livraison;


    // 8. Modifier la commande
    // Le menu_id n'est volontairement jamais modifié.

    $sqlUpdate = "
        UPDATE commandes

        SET
            nb_personnes = :nb_personnes,
            prix_total = :prix_total,
            date_prestation = :date_prestation,
            heure_prestation = :heure_prestation,
            lieu_prestation = :lieu_prestation,
            adresse_prestation = :adresse_prestation,
            distance_km = :distance_km,
            frais_livraison = :frais_livraison,
            remise_pourcentage = :remise_pourcentage

        WHERE id = :id
        AND user_id = :user_id
        AND statut = 'en attente'
    ";

    $stmtUpdate = $pdo->prepare(
        $sqlUpdate
    );


    $stmtUpdate->execute([

        ':nb_personnes' =>
            $nb_personnes,

        ':prix_total' =>
            $prix_total,

        ':date_prestation' =>
            $date_prestation,

        ':heure_prestation' =>
            $heure_prestation,

        ':lieu_prestation' =>
            $lieu_prestation,

        ':adresse_prestation' =>
            $adresse_prestation,

        ':distance_km' =>
            $distance_km,

        ':frais_livraison' =>
            $frais_livraison,

        ':remise_pourcentage' =>
            $remise_pourcentage,

        ':id' =>
            $id,

        ':user_id' =>
            $user_id
    ]);


    header(
        "Location: mes-commandes.php"
    );

    exit;
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

    <title>
        Modifier ma commande
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        main {
            max-width: 600px;
            margin: auto;

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;

            margin-top: 15px;
            margin-bottom: 5px;

            font-weight: bold;
        }

        input {
            width: 100%;

            padding: 10px;

            box-sizing: border-box;

            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            margin-top: 20px;

            padding: 10px 18px;

            background-color: black;
            color: white;

            border: none;
            border-radius: 5px;

            cursor: pointer;
        }

        button:hover {
            background-color: #444;
        }

        .retour {
            display: inline-block;
            margin-top: 20px;
        }

    </style>

</head>


<body>

<main>

    <h1>
        Modifier ma commande
    </h1>


    <h2>
        <?php
        echo htmlspecialchars(
            $commande['titre']
        );
        ?>
    </h2>


    <p>
        Le menu ne peut pas être modifié.
    </p>


    <form method="POST">

        <label for="nb_personnes">
            Nombre de personnes
        </label>

        <input
            type="number"
            id="nb_personnes"
            name="nb_personnes"
            min="<?php
            echo (int) $commande['nb_personnes_min'];
            ?>"
            value="<?php
            echo (int) $commande['nb_personnes'];
            ?>"
            required
        >


        <label for="date_prestation">
            Date de prestation
        </label>

        <input
            type="date"
            id="date_prestation"
            name="date_prestation"
            value="<?php
            echo htmlspecialchars(
                $commande['date_prestation'] ?? ''
            );
            ?>"
            required
        >


        <label for="heure_prestation">
            Heure de prestation
        </label>

        <input
            type="time"
            id="heure_prestation"
            name="heure_prestation"
            value="<?php
            echo htmlspecialchars(
                $commande['heure_prestation'] ?? ''
            );
            ?>"
            required
        >


        <label for="lieu_prestation">
            Lieu
        </label>

        <input
            type="text"
            id="lieu_prestation"
            name="lieu_prestation"
            value="<?php
            echo htmlspecialchars(
                $commande['lieu_prestation'] ?? ''
            );
            ?>"
            required
        >


        <label for="adresse_prestation">
            Adresse
        </label>

        <input
            type="text"
            id="adresse_prestation"
            name="adresse_prestation"
            value="<?php
            echo htmlspecialchars(
                $commande['adresse_prestation'] ?? ''
            );
            ?>"
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
            value="<?php
            echo htmlspecialchars(
                $commande['distance_km'] ?? 0
            );
            ?>"
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

</main>

</body>

</html>