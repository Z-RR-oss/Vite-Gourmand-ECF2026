<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// 2. Autoriser uniquement employé / admin
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// 3. Vérifier l'identifiant
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit("ID de commande invalide.");
}

$id = (int) $id;


// 4. Récupérer la commande
$sql = "
    SELECT
        commandes.*,
        menus.titre,
        users.nom,
        users.prenom,
        users.email
    FROM commandes

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    INNER JOIN users
        ON commandes.user_id = users.id

    WHERE commandes.id = :id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$commande = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$commande) {
    exit("Commande introuvable.");
}


// 5. Vérifier que la commande attend bien le matériel
if (
    $commande['statut'] !==
    'en attente du retour de matériel'
) {
    exit(
        "Cette commande n'est pas en attente du retour de matériel."
    );
}


// 6. Vérifier que le matériel n'est pas déjà retourné
if ((int) $commande['materiel_retourne'] === 1) {
    exit("Le matériel a déjà été retourné.");
}


// 7. Vérifier qu'une date de début existe
if (empty($commande['date_debut_attente_retour'])) {
    exit(
        "La date de début d'attente du matériel est manquante."
    );
}


// 8. Fonction de calcul des jours ouvrés
// Ici : lundi à vendredi.
// Les jours fériés ne sont pas déduits dans cette version.
function calculerJoursOuvres(
    string $dateDebut,
    string $dateFin
): int {

    $debut = new DateTime($dateDebut);
    $fin = new DateTime($dateFin);

    // On commence à compter à partir du lendemain
    $debut->modify('+1 day');

    $joursOuvres = 0;

    while ($debut->setTime(0, 0) <= $fin->setTime(0, 0)) {

        $jourSemaine = (int) $debut->format('N');

        // 1 = lundi
        // 5 = vendredi
        if ($jourSemaine <= 5) {
            $joursOuvres++;
        }

        $debut->modify('+1 day');
    }

    return $joursOuvres;
}


// 9. Calcul du retard
$maintenant = new DateTime();

$joursOuvres = calculerJoursOuvres(
    $commande['date_debut_attente_retour'],
    $maintenant->format('Y-m-d H:i:s')
);


// Frais de 600 € au-delà de 10 jours ouvrés
$fraisRetard = 0;

if ($joursOuvres > 10) {
    $fraisRetard = 600;
}


// 10. Confirmation du retour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();


        // Mettre à jour la commande
        $sqlUpdate = "
            UPDATE commandes

            SET
                materiel_retourne = 1,
                date_retour_materiel = NOW(),
                frais_retard_materiel = :frais_retard,
                statut = 'terminée'

            WHERE id = :id
            AND statut = 'en attente du retour de matériel'
            AND materiel_retourne = 0
        ";

        $stmtUpdate = $pdo->prepare(
            $sqlUpdate
        );

        $stmtUpdate->execute([
            ':frais_retard' => $fraisRetard,
            ':id' => $id
        ]);


        // Vérifier que la commande a bien été modifiée
        if ($stmtUpdate->rowCount() === 0) {

            throw new Exception(
                "La commande ne peut plus être modifiée."
            );
        }


        // Ajouter le statut terminé à l'historique
        $sqlHistorique = "
            INSERT INTO historique_statuts (
                commande_id,
                statut,
                modifie_par
            )

            VALUES (
                :commande_id,
                'terminée',
                :modifie_par
            )
        ";

        $stmtHistorique = $pdo->prepare(
            $sqlHistorique
        );

        $stmtHistorique->execute([
            ':commande_id' => $id,
            ':modifie_par' => $_SESSION['user_id']
        ]);


        $pdo->commit();


        header(
            "Location: admin-commandes.php"
        );

        exit;


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            "Erreur retour matériel : "
            . $e->getMessage()
        );

        exit(
            "Une erreur est survenue lors de l'enregistrement du retour."
        );
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
        Retour du matériel - Vite & Gourmand
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
                0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .infos {
            background: #f5f5f5;

            padding: 15px;

            border-radius: 8px;

            margin-bottom: 20px;
        }

        .alerte {
            background: #ffe0e0;

            color: #900;

            padding: 15px;

            border-radius: 8px;

            margin-top: 20px;
        }

        .ok {
            background: #e4f7e4;

            color: #165b16;

            padding: 15px;

            border-radius: 8px;

            margin-top: 20px;
        }

        button {
            margin-top: 20px;

            padding: 10px 16px;

            border: none;

            border-radius: 5px;

            background: black;

            color: white;

            cursor: pointer;
        }

        button:hover {
            background: #444;
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
        Retour du matériel
    </h1>


    <div class="infos">

        <h2>
            Commande n°
            <?php echo (int) $commande['id']; ?>
        </h2>


        <p>
            <strong>Menu :</strong>

            <?php
            echo htmlspecialchars(
                $commande['titre']
            );
            ?>
        </p>


        <p>
            <strong>Client :</strong>

            <?php
            echo htmlspecialchars(
                $commande['prenom']
                . ' '
                . $commande['nom']
            );
            ?>
        </p>


        <p>
            <strong>Email :</strong>

            <?php
            echo htmlspecialchars(
                $commande['email']
            );
            ?>
        </p>


        <p>
            <strong>
                Début de l'attente du matériel :
            </strong>

            <?php
            echo htmlspecialchars(
                $commande['date_debut_attente_retour']
            );
            ?>
        </p>


        <p>
            <strong>
                Nombre de jours ouvrés écoulés :
            </strong>

            <?php
            echo $joursOuvres;
            ?>
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

</main>

</body>

</html>