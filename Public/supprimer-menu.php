<?php

session_start();
require_once '../Config/database.php';


// Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// Autoriser admin / employé
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// Vérifier l'identifiant
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit("ID de menu invalide.");
}

$id = (int) $id;


// Récupérer le menu
$sqlMenu = "
    SELECT *
    FROM menus
    WHERE id = :id
";

$stmtMenu = $pdo->prepare($sqlMenu);

$stmtMenu->execute([
    ':id' => $id
]);

$menu = $stmtMenu->fetch(PDO::FETCH_ASSOC);


if (!$menu) {
    exit("Menu introuvable.");
}


// Vérifier si des commandes utilisent ce menu
$sqlCommandes = "
    SELECT COUNT(*)
    FROM commandes
    WHERE menu_id = :menu_id
";

$stmtCommandes = $pdo->prepare(
    $sqlCommandes
);

$stmtCommandes->execute([
    ':menu_id' => $id
]);

$nombreCommandes = (int)
    $stmtCommandes->fetchColumn();


// Confirmation de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        /*
         * Si le menu est déjà utilisé dans une commande,
         * on le désactive au lieu de le supprimer.
         *
         * Cela permet de conserver l'historique
         * des anciennes commandes.
         */
        if ($nombreCommandes > 0) {

            $sql = "
                UPDATE menus
                SET actif = 0
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':id' => $id
            ]);

        } else {

            // Si aucune commande ne l'utilise,
            // suppression réelle possible.
            $sql = "
                DELETE FROM menus
                WHERE id = :id
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':id' => $id
            ]);
        }


        header(
            "Location: admin-menus.php"
        );

        exit;


    } catch (PDOException $e) {

        error_log(
            "Erreur suppression menu : "
            . $e->getMessage()
        );

        exit(
            "Une erreur est survenue lors de la suppression du menu."
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
        Supprimer un menu - Vite & Gourmand
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

        .alerte {
            background: #fff0f0;

            padding: 15px;

            border-radius: 8px;

            margin: 20px 0;
        }

        button {
            padding: 10px 16px;

            border: none;

            border-radius: 5px;

            background: #b00020;

            color: white;

            cursor: pointer;
        }

        .retour {
            display: inline-block;

            margin-left: 15px;
        }

    </style>

</head>


<body>

<main>

    <h1>
        Supprimer le menu
    </h1>


    <h2>

        <?php
        echo htmlspecialchars(
            $menu['titre']
        );
        ?>

    </h2>


    <?php if ($nombreCommandes > 0): ?>

        <div class="alerte">

            <strong>
                Ce menu est lié à
                <?php echo $nombreCommandes; ?>
                commande(s).
            </strong>

            <p>
                Il ne sera pas supprimé définitivement :
                il sera rendu inactif afin de conserver
                l'historique des commandes.
            </p>

        </div>

    <?php else: ?>

        <div class="alerte">

            <p>
                Ce menu n'est lié à aucune commande.
                Il peut être supprimé définitivement.
            </p>

        </div>

    <?php endif; ?>


    <form method="POST">

        <button type="submit">
            Confirmer la suppression
        </button>

        <a
            class="retour"
            href="admin-menus.php"
        >
            Annuler
        </a>

    </form>

</main>

</body>

</html>