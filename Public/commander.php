<?php

session_start();
require_once '../Config/database.php';


// --------------------------------------------------
// FONCTIONS
// --------------------------------------------------


// Construire une date/heure de prestation valide
function creerDatePrestation(
    string $date,
    string $heure
): ?DateTime {

    $datePrestation = DateTime::createFromFormat(
        'Y-m-d H:i',
        $date . ' ' . $heure
    );

    if ($datePrestation === false) {
        return null;
    }

    $erreurs = DateTime::getLastErrors();

    if (
        $erreurs !== false
        && (
            $erreurs['warning_count'] > 0
            || $erreurs['error_count'] > 0
        )
    ) {
        return null;
    }

    return $datePrestation;
}


// Calculer le prix de la commande
function calculerPrixCommande(
    array $menu,
    int $nbPersonnes,
    float $distanceKm
): array {

    $minimum = (int) $menu['nb_personnes_min'];
    $prixMenu = (float) $menu['prix'];


    $prixParPersonne =
        $prixMenu / $minimum;


    $prixRepas =
        $prixParPersonne
        * $nbPersonnes;


    // Remise de 10 %
    // si minimum + 5 personnes
    $remisePourcentage = 0;

    if (
        $nbPersonnes
        >= $minimum + 5
    ) {
        $remisePourcentage = 10;
    }


    $montantRemise =
        $prixRepas
        * ($remisePourcentage / 100);


    $prixApresRemise =
        $prixRepas
        - $montantRemise;


    // Frais de livraison hors Bordeaux
    $fraisLivraison = 0;

    if ($distanceKm > 0) {

        $fraisLivraison =
            5
            + (0.59 * $distanceKm);
    }


    $prixTotal =
        $prixApresRemise
        + $fraisLivraison;


    return [
        'prix_repas' =>
            round($prixRepas, 2),

        'remise_pourcentage' =>
            $remisePourcentage,

        'montant_remise' =>
            round($montantRemise, 2),

        'frais_livraison' =>
            round($fraisLivraison, 2),

        'prix_total' =>
            round($prixTotal, 2)
    ];
}


// Vérifier le délai minimum avant prestation
function verifierDelaiCommande(
    string $date,
    string $heure,
    int $delaiHeures
): ?string {

    $datePrestation =
        creerDatePrestation(
            $date,
            $heure
        );


    if (!$datePrestation) {

        return
            "La date ou l'heure de prestation est invalide.";
    }


    $maintenant = new DateTime();

    $dateMinimum =
        clone $maintenant;


    if ($delaiHeures > 0) {

        $dateMinimum->modify(
            '+' . $delaiHeures . ' hours'
        );
    }


    if ($datePrestation < $dateMinimum) {

        return
            "Ce menu doit être commandé au moins "
            . $delaiHeures
            . " heure(s) avant la prestation.";
    }


    return null;
}


// --------------------------------------------------
// CONNEXION
// --------------------------------------------------


// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


// --------------------------------------------------
// MENU
// --------------------------------------------------


$id = $_GET['id'] ?? null;


if (!$id || !is_numeric($id)) {

    exit("Menu invalide.");
}


$id = (int) $id;


// Récupérer le menu
$sql = "
    SELECT *
    FROM menus
    WHERE id = :id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$menu = $stmt->fetch(
    PDO::FETCH_ASSOC
);


if (!$menu) {

    exit("Menu introuvable.");
}


// Vérifier que le menu est actif
if ((int) $menu['actif'] !== 1) {

    exit(
        "Ce menu n'est actuellement pas disponible à la commande."
    );
}


// Vérifier le stock dès l'arrivée sur la page
if ((int) $menu['stock_disponible'] <= 0) {

    exit(
        "Ce menu est actuellement épuisé."
    );
}


// --------------------------------------------------
// UTILISATEUR
// --------------------------------------------------


$sqlUser = "
    SELECT *
    FROM users
    WHERE id = :id
";

$stmtUser = $pdo->prepare(
    $sqlUser
);

$stmtUser->execute([
    ':id' => $_SESSION['user_id']
]);

$user = $stmtUser->fetch(
    PDO::FETCH_ASSOC
);


if (!$user) {

    exit(
        "Utilisateur introuvable."
    );
}


// --------------------------------------------------
// VALEURS PAR DÉFAUT
// --------------------------------------------------


$adressePrestation =
    $user['adresse'];

$datePrestation = '';

$heurePrestation = '';

$lieuPrestation = '';

$nbPersonnes =
    (int) $menu['nb_personnes_min'];

$distanceKm = 0;


$recap = false;

$erreur = '';


// Variables prix
$prixRepas = 0;

$remisePourcentage = 0;

$montantRemise = 0;

$fraisLivraison = 0;

$prixTotal = 0;


// --------------------------------------------------
// TRAITEMENT DU FORMULAIRE
// --------------------------------------------------


if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    $adressePrestation = trim(
        $_POST['adresse_prestation']
        ?? ''
    );


    $datePrestation = trim(
        $_POST['date_prestation']
        ?? ''
    );


    $heurePrestation = trim(
        $_POST['heure_prestation']
        ?? ''
    );


    $lieuPrestation = trim(
        $_POST['lieu_prestation']
        ?? ''
    );


    $nbPersonnes = (int) (
        $_POST['nb_personnes']
        ?? 0
    );


    $distanceKm = (float) (
        $_POST['distance_km']
        ?? 0
    );


    $action =
        $_POST['action']
        ?? 'recap';


    // --------------------------------------------------
    // VALIDATIONS
    // --------------------------------------------------


    if (
        $adressePrestation === ''
        || $datePrestation === ''
        || $heurePrestation === ''
        || $lieuPrestation === ''
    ) {

        $erreur =
            "Tous les champs sont obligatoires.";
    }


    if (
        $erreur === ''
        && $nbPersonnes
        < (int) $menu['nb_personnes_min']
    ) {

        $erreur =
            "Le nombre de personnes doit être au minimum de "
            . (int) $menu['nb_personnes_min']
            . ".";
    }


    if (
        $erreur === ''
        && $distanceKm < 0
    ) {

        $erreur =
            "La distance ne peut pas être négative.";
    }


    // Vérifier le stock
    if (
        $erreur === ''
        && (int) $menu['stock_disponible'] <= 0
    ) {

        $erreur =
            "Ce menu est actuellement épuisé.";
    }


    // Vérifier le délai
    if ($erreur === '') {

        $erreurDelai =
            verifierDelaiCommande(
                $datePrestation,
                $heurePrestation,
                (int) $menu[
                    'delai_commande_heures'
                ]
            );


        if ($erreurDelai !== null) {

            $erreur = $erreurDelai;
        }
    }


    // --------------------------------------------------
    // CALCUL DU PRIX
    // --------------------------------------------------


    if ($erreur === '') {

        $prix =
            calculerPrixCommande(
                $menu,
                $nbPersonnes,
                $distanceKm
            );


        $prixRepas =
            $prix['prix_repas'];

        $remisePourcentage =
            $prix['remise_pourcentage'];

        $montantRemise =
            $prix['montant_remise'];

        $fraisLivraison =
            $prix['frais_livraison'];

        $prixTotal =
            $prix['prix_total'];
    }


    // --------------------------------------------------
    // ÉTAPE 1 : RÉCAPITULATIF
    // --------------------------------------------------


    if (
        $erreur === ''
        && $action === 'recap'
    ) {

        $recap = true;
    }


    // --------------------------------------------------
    // ÉTAPE 2 : CONFIRMATION
    // --------------------------------------------------


    if (
        $erreur === ''
        && $action === 'confirm'
    ) {


        try {

            /*
             * Transaction :
             * - verrouiller le menu
             * - revérifier stock et délai
             * - créer la commande
             * - diminuer le stock
             */

            $pdo->beginTransaction();


            // Relire et verrouiller le menu
            $sqlMenuConfirmation = "
                SELECT *
                FROM menus
                WHERE id = :id
                FOR UPDATE
            ";

            $stmtMenuConfirmation =
                $pdo->prepare(
                    $sqlMenuConfirmation
                );


            $stmtMenuConfirmation->execute([
                ':id' => $id
            ]);


            $menuConfirmation =
                $stmtMenuConfirmation->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$menuConfirmation) {

                throw new Exception(
                    "Menu introuvable."
                );
            }


            if (
                (int) $menuConfirmation['actif']
                !== 1
            ) {

                throw new Exception(
                    "Ce menu n'est plus disponible."
                );
            }


            // Revérifier le stock
            if (
                (int)
                $menuConfirmation[
                    'stock_disponible'
                ]
                <= 0
            ) {

                throw new Exception(
                    "Ce menu vient d'être épuisé. "
                    . "La commande n'a pas été enregistrée."
                );
            }


            // Revérifier le minimum
            if (
                $nbPersonnes
                < (int)
                    $menuConfirmation[
                        'nb_personnes_min'
                    ]
            ) {

                throw new Exception(
                    "Le nombre de personnes est insuffisant."
                );
            }


            // Revérifier le délai
            $erreurDelaiConfirmation =
                verifierDelaiCommande(
                    $datePrestation,
                    $heurePrestation,
                    (int)
                    $menuConfirmation[
                        'delai_commande_heures'
                    ]
                );


            if (
                $erreurDelaiConfirmation
                !== null
            ) {

                throw new Exception(
                    $erreurDelaiConfirmation
                );
            }


            // Recalculer le prix
            $prixConfirmation =
                calculerPrixCommande(
                    $menuConfirmation,
                    $nbPersonnes,
                    $distanceKm
                );


            $prixRepas =
                $prixConfirmation[
                    'prix_repas'
                ];

            $remisePourcentage =
                $prixConfirmation[
                    'remise_pourcentage'
                ];

            $montantRemise =
                $prixConfirmation[
                    'montant_remise'
                ];

            $fraisLivraison =
                $prixConfirmation[
                    'frais_livraison'
                ];

            $prixTotal =
                $prixConfirmation[
                    'prix_total'
                ];


            // Créer la commande
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


            $stmtCommande =
                $pdo->prepare(
                    $sqlCommande
                );


            $stmtCommande->execute([

                ':user_id' =>
                    $_SESSION['user_id'],

                ':menu_id' =>
                    $menuConfirmation['id'],

                ':nb_personnes' =>
                    $nbPersonnes,

                ':prix_total' =>
                    $prixTotal,

                ':date_prestation' =>
                    $datePrestation,

                ':heure_prestation' =>
                    $heurePrestation,

                ':lieu_prestation' =>
                    $lieuPrestation,

                ':adresse_prestation' =>
                    $adressePrestation,

                ':distance_km' =>
                    $distanceKm,

                ':frais_livraison' =>
                    $fraisLivraison,

                ':remise_pourcentage' =>
                    $remisePourcentage
            ]);


            // Diminuer le stock de 1
            $sqlStock = "
                UPDATE menus

                SET stock_disponible =
                    stock_disponible - 1

                WHERE id = :id
                AND stock_disponible > 0
            ";


            $stmtStock =
                $pdo->prepare(
                    $sqlStock
                );


            $stmtStock->execute([
                ':id' =>
                    $menuConfirmation['id']
            ]);


            if (
                $stmtStock->rowCount()
                !== 1
            ) {

                throw new Exception(
                    "Le stock de ce menu n'est plus disponible."
                );
            }


            $pdo->commit();


            header(
                "Location: mes-commandes.php"
            );

            exit;


        } catch (Throwable $e) {


            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }


            error_log(
                "Erreur confirmation commande : "
                . $e->getMessage()
            );


            $erreur =
                $e->getMessage();
        }
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

    <title>
        Commander - Vite & Gourmand
    </title>


    <style>

        body {
            font-family: Arial, sans-serif;

            background: #f4f4f4;

            padding: 20px;
        }


        main {
            max-width: 750px;

            margin: auto;

            padding: 30px;

            background: white;

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
            width: 100%;

            margin-top: 25px;

            padding: 13px;

            border: none;

            border-radius: 6px;

            background: black;
            color: white;

            font-size: 16px;

            cursor: pointer;
        }


        .erreur {
            padding: 12px;

            margin-bottom: 20px;

            background: #ffdede;
            color: #8b0000;

            border-radius: 6px;
        }


        .infos-menu {
            padding: 15px;

            margin-bottom: 20px;

            background: #f5f5f5;

            border-radius: 8px;
        }


        .stock {
            font-weight: bold;
        }


        .recap {
            margin-top: 30px;

            padding: 20px;

            background: #f5f5f5;

            border-radius: 8px;
        }

    </style>

</head>


<body>


<main>


    <h1>

        <?php
        echo htmlspecialchars(
            $menu['titre']
        );
        ?>

    </h1>


    <p>

        <?php
        echo htmlspecialchars(
            $menu['description']
        );
        ?>

    </p>


    <div class="infos-menu">


        <p>

            <strong>
                Prix de base :
            </strong>

            <?php
            echo number_format(
                $menu['prix'],
                2,
                ',',
                ' '
            );
            ?>

            €

        </p>


        <p>

            <strong>
                Minimum :
            </strong>

            <?php
            echo (int)
                $menu['nb_personnes_min'];
            ?>

            personnes

        </p>


        <p class="stock">

            Commandes encore disponibles :

            <?php
            echo (int)
                $menu['stock_disponible'];
            ?>

        </p>


        <p>

            Délai minimum de commande :

            <?php
            echo (int)
                $menu[
                    'delai_commande_heures'
                ];
            ?>

            heure(s)

        </p>


    </div>


    <?php if ($erreur !== ''): ?>

        <p class="erreur">

            <?php
            echo htmlspecialchars(
                $erreur
            );
            ?>

        </p>

    <?php endif; ?>


    <form method="post">


        <h2>
            Votre commande
        </h2>


        <label for="nom">
            Nom
        </label>

        <input
            type="text"
            id="nom"
            value="<?php
            echo htmlspecialchars(
                $user['nom']
            );
            ?>"
            disabled
        >


        <label for="prenom">
            Prénom
        </label>

        <input
            type="text"
            id="prenom"
            value="<?php
            echo htmlspecialchars(
                $user['prenom']
            );
            ?>"
            disabled
        >


        <label for="adresse_prestation">
            Adresse de prestation
        </label>

        <input
            type="text"
            id="adresse_prestation"
            name="adresse_prestation"
            value="<?php
            echo htmlspecialchars(
                $adressePrestation
            );
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
                $datePrestation
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
                $heurePrestation
            );
            ?>"
            required
        >


        <label for="lieu_prestation">
            Lieu de prestation
        </label>

        <input
            type="text"
            id="lieu_prestation"
            name="lieu_prestation"
            value="<?php
            echo htmlspecialchars(
                $lieuPrestation
            );
            ?>"
            required
        >


        <label for="nb_personnes">
            Nombre de personnes
        </label>

        <input
            type="number"
            id="nb_personnes"
            name="nb_personnes"
            min="<?php
            echo (int)
                $menu['nb_personnes_min'];
            ?>"
            value="<?php
            echo $nbPersonnes;
            ?>"
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
            value="<?php
            echo $distanceKm;
            ?>"
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
                    <?php
                    echo htmlspecialchars(
                        $menu['titre']
                    );
                    ?>
                </strong>

            </p>


            <p>

                Date :

                <?php
                echo htmlspecialchars(
                    $datePrestation
                );
                ?>

                à

                <?php
                echo htmlspecialchars(
                    $heurePrestation
                );
                ?>

            </p>


            <p>

                Nombre de personnes :

                <?php
                echo $nbPersonnes;
                ?>

            </p>


            <p>

                Prix du repas :

                <?php
                echo number_format(
                    $prixRepas,
                    2,
                    ',',
                    ' '
                );
                ?>

                €

            </p>


            <p>

                Remise :

                <?php
                echo $remisePourcentage;
                ?>

                %

            </p>


            <p>

                Montant de la remise :

                <?php
                echo number_format(
                    $montantRemise,
                    2,
                    ',',
                    ' '
                );
                ?>

                €

            </p>


            <p>

                Frais de livraison :

                <?php
                echo number_format(
                    $fraisLivraison,
                    2,
                    ',',
                    ' '
                );
                ?>

                €

            </p>


            <h3>

                Total :

                <?php
                echo number_format(
                    $prixTotal,
                    2,
                    ',',
                    ' '
                );
                ?>

                €

            </h3>


            <form method="post">


                <input
                    type="hidden"
                    name="adresse_prestation"
                    value="<?php
                    echo htmlspecialchars(
                        $adressePrestation
                    );
                    ?>"
                >


                <input
                    type="hidden"
                    name="date_prestation"
                    value="<?php
                    echo htmlspecialchars(
                        $datePrestation
                    );
                    ?>"
                >


                <input
                    type="hidden"
                    name="heure_prestation"
                    value="<?php
                    echo htmlspecialchars(
                        $heurePrestation
                    );
                    ?>"
                >


                <input
                    type="hidden"
                    name="lieu_prestation"
                    value="<?php
                    echo htmlspecialchars(
                        $lieuPrestation
                    );
                    ?>"
                >


                <input
                    type="hidden"
                    name="nb_personnes"
                    value="<?php
                    echo $nbPersonnes;
                    ?>"
                >


                <input
                    type="hidden"
                    name="distance_km"
                    value="<?php
                    echo $distanceKm;
                    ?>"
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


</main>


</body>

</html>