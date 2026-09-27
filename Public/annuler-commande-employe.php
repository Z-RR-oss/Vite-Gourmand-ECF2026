<?php

session_start();
require_once '../Config/database.php';


// --------------------------------------------------
// 1. VÉRIFIER LA CONNEXION
// --------------------------------------------------

if (
    !isset(
        $_SESSION['user_id'],
        $_SESSION['role']
    )
) {

    header("Location: login.php");
    exit;
}


// --------------------------------------------------
// 2. AUTORISATION
// --------------------------------------------------

if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {

    exit("Accès refusé.");
}


// --------------------------------------------------
// 3. IDENTIFIANT
// --------------------------------------------------

$id =
    $_GET['id']
    ?? null;


if (!$id || !is_numeric($id)) {

    exit(
        "ID de commande invalide."
    );
}


$id = (int) $id;


// --------------------------------------------------
// 4. RÉCUPÉRER LA COMMANDE POUR L'AFFICHAGE
// --------------------------------------------------

$sql = "
    SELECT
        commandes.*,

        menus.titre,

        users.nom,
        users.prenom,
        users.email,
        users.gsm

    FROM commandes

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    INNER JOIN users
        ON commandes.user_id = users.id

    WHERE commandes.id = :id
";


$stmt =
    $pdo->prepare(
        $sql
    );


$stmt->execute([
    ':id' => $id
]);


$commande =
    $stmt->fetch(
        PDO::FETCH_ASSOC
    );


if (!$commande) {

    exit(
        "Commande introuvable."
    );
}


// Empêcher une nouvelle annulation
if (
    $commande['statut']
    === 'annulée'
) {

    exit(
        "Cette commande est déjà annulée."
    );
}


// Une commande terminée
// ne peut plus être annulée
if (
    $commande['statut']
    === 'terminée'
) {

    exit(
        "Une commande terminée ne peut plus être annulée."
    );
}


$erreur = '';


// --------------------------------------------------
// 5. TRAITEMENT DU FORMULAIRE
// --------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {


    $modeContact =
        trim(
            $_POST['mode_contact']
            ?? ''
        );


    $motif =
        trim(
            $_POST['motif']
            ?? ''
        );


    $modesAutorises = [
        'Téléphone',
        'Email',
        'SMS'
    ];


    if (
        !in_array(
            $modeContact,
            $modesAutorises,
            true
        )
    ) {

        $erreur =
            "Veuillez sélectionner un mode de contact valide.";
    }


    if ($motif === '') {

        $erreur =
            "Le motif de l'annulation est obligatoire.";
    }


    // --------------------------------------------------
    // 6. ANNULATION
    // --------------------------------------------------

    if ($erreur === '') {


        try {

            $pdo->beginTransaction();


            /*
             * Relire et verrouiller la commande.
             * Cela empêche deux annulations
             * concurrentes de restaurer
             * deux fois le stock.
             */
            $sqlVerif = "
                SELECT
                    id,
                    menu_id,
                    statut

                FROM commandes

                WHERE id = :id

                FOR UPDATE
            ";


            $stmtVerif =
                $pdo->prepare(
                    $sqlVerif
                );


            $stmtVerif->execute([
                ':id' => $id
            ]);


            $commandeVerif =
                $stmtVerif->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$commandeVerif) {

                throw new Exception(
                    "Commande introuvable."
                );
            }


            if (
                $commandeVerif['statut']
                === 'annulée'
            ) {

                throw new Exception(
                    "Cette commande est déjà annulée."
                );
            }


            if (
                $commandeVerif['statut']
                === 'terminée'
            ) {

                throw new Exception(
                    "Une commande terminée ne peut plus être annulée."
                );
            }


            // --------------------------------------------------
            // 7. PASSER LA COMMANDE À ANNULÉE
            // --------------------------------------------------

            $sqlUpdate = "
                UPDATE commandes

                SET
                    statut = 'annulée',

                    mode_contact_annulation =
                        :mode_contact,

                    motif_annulation =
                        :motif,

                    date_annulation =
                        NOW()

                WHERE id = :id

                AND statut <> 'annulée'
                AND statut <> 'terminée'
            ";


            $stmtUpdate =
                $pdo->prepare(
                    $sqlUpdate
                );


            $stmtUpdate->execute([

                ':mode_contact' =>
                    $modeContact,

                ':motif' =>
                    $motif,

                ':id' =>
                    $id
            ]);


            if (
                $stmtUpdate->rowCount()
                !== 1
            ) {

                throw new Exception(
                    "La commande ne peut plus être annulée."
                );
            }


            // --------------------------------------------------
            // 8. RESTAURER LE STOCK
            // --------------------------------------------------

            $sqlStock = "
                UPDATE menus

                SET stock_disponible =
                    stock_disponible + 1

                WHERE id = :menu_id
            ";


            $stmtStock =
                $pdo->prepare(
                    $sqlStock
                );


            $stmtStock->execute([
                ':menu_id' =>
                    $commandeVerif['menu_id']
            ]);


            if (
                $stmtStock->rowCount()
                !== 1
            ) {

                throw new Exception(
                    "Impossible de restaurer le stock du menu."
                );
            }


            // --------------------------------------------------
            // 9. HISTORIQUE
            // --------------------------------------------------

            $sqlHistorique = "
                INSERT INTO historique_statuts (
                    commande_id,
                    statut,
                    modifie_par
                )

                VALUES (
                    :commande_id,
                    'annulée',
                    :modifie_par
                )
            ";


            $stmtHistorique =
                $pdo->prepare(
                    $sqlHistorique
                );


            $stmtHistorique->execute([

                ':commande_id' =>
                    $id,

                ':modifie_par' =>
                    $_SESSION['user_id']
            ]);


            $pdo->commit();


            header(
                "Location: admin-commandes.php"
            );

            exit;


        } catch (Throwable $e) {


            if (
                $pdo->inTransaction()
            ) {

                $pdo->rollBack();
            }


            error_log(
                "Erreur annulation employé : "
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
        Annuler une commande - Vite & Gourmand
    </title>


    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }


        main {
            max-width: 650px;
            margin: auto;

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 2px 8px
                rgba(0, 0, 0, 0.1);
        }


        .commande-info {
            background: #f5f5f5;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 20px;
        }


        label {
            display: block;

            margin-top: 15px;
            margin-bottom: 5px;

            font-weight: bold;
        }


        select,
        textarea {
            width: 100%;

            padding: 10px;

            box-sizing: border-box;

            border: 1px solid #ccc;

            border-radius: 5px;
        }


        textarea {
            min-height: 120px;
            resize: vertical;
        }


        button {
            margin-top: 20px;

            padding: 10px 16px;

            border: none;

            border-radius: 5px;

            background-color: #b00020;

            color: white;

            cursor: pointer;
        }


        button:hover {
            background-color: #800018;
        }


        .erreur {
            background: #ffdede;

            color: #8b0000;

            padding: 10px;

            border-radius: 5px;

            margin-bottom: 15px;
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
        Annuler une commande
    </h1>


    <div class="commande-info">


        <h2>
            Commande n°
            <?php
            echo (int)
                $commande['id'];
            ?>
        </h2>


        <p>

            <strong>
                Menu :
            </strong>

            <?php
            echo htmlspecialchars(
                $commande['titre']
            );
            ?>

        </p>


        <p>

            <strong>
                Client :
            </strong>

            <?php
            echo htmlspecialchars(
                $commande['prenom']
                . ' '
                . $commande['nom']
            );
            ?>

        </p>


        <p>

            <strong>
                Email :
            </strong>

            <?php
            echo htmlspecialchars(
                $commande['email']
            );
            ?>

        </p>


        <p>

            <strong>
                Téléphone :
            </strong>

            <?php
            echo htmlspecialchars(
                $commande['gsm']
            );
            ?>

        </p>


        <p>

            <strong>
                Statut actuel :
            </strong>

            <?php
            echo htmlspecialchars(
                $commande['statut']
            );
            ?>

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


    <p>
        Avant d'annuler la commande,
        le client doit être contacté.
    </p>


    <form method="POST">


        <label for="mode_contact">
            Mode de contact utilisé
        </label>


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


        <label for="motif">
            Motif de l'annulation
        </label>


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


</main>


</body>

</html>