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
    exit("ID de plat invalide.");
}

$id = (int) $id;


// Récupérer le plat
$sql = "
    SELECT *
    FROM plats
    WHERE id = :id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id
]);

$plat = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$plat) {
    exit("Plat introuvable.");
}


// Vérifier combien de menus utilisent ce plat
$sqlMenus = "
    SELECT COUNT(*)
    FROM menu_plat
    WHERE plat_id = :plat_id
";

$stmtMenus = $pdo->prepare(
    $sqlMenus
);

$stmtMenus->execute([
    ':plat_id' => $id
]);

$nombreMenus = (int)
    $stmtMenus->fetchColumn();


// Suppression après confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {

        $pdo->beginTransaction();


        // Supprimer les liens avec les menus
        $sqlMenuPlat = "
            DELETE FROM menu_plat
            WHERE plat_id = :plat_id
        ";

        $stmtMenuPlat = $pdo->prepare(
            $sqlMenuPlat
        );

        $stmtMenuPlat->execute([
            ':plat_id' => $id
        ]);


        // Supprimer les liens avec les allergènes
        $sqlAllergenes = "
            DELETE FROM plat_allergene
            WHERE plat_id = :plat_id
        ";

        $stmtAllergenes = $pdo->prepare(
            $sqlAllergenes
        );

        $stmtAllergenes->execute([
            ':plat_id' => $id
        ]);


        // Supprimer le plat
        $sqlDelete = "
            DELETE FROM plats
            WHERE id = :id
        ";

        $stmtDelete = $pdo->prepare(
            $sqlDelete
        );

        $stmtDelete->execute([
            ':id' => $id
        ]);


        $pdo->commit();


        header(
            "Location: admin-plats.php"
        );

        exit;


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }


        error_log(
            "Erreur suppression plat : "
            . $e->getMessage()
        );


        exit(
            "Une erreur est survenue lors de la suppression du plat."
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
        Supprimer un plat - Vite & Gourmand
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

            margin: 20px 0;

            border-radius: 8px;
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
        Supprimer le plat
    </h1>


    <h2>

        <?php
        echo htmlspecialchars(
            $plat['nom']
        );
        ?>

    </h2>


    <div class="alerte">

        <?php if ($nombreMenus > 0): ?>

            <p>

                Ce plat appartient actuellement à

                <strong>
                    <?php echo $nombreMenus; ?>
                    menu(s).
                </strong>

            </p>

            <p>
                Sa suppression le retirera également
                de ces menus.
            </p>

        <?php else: ?>

            <p>
                Ce plat n'est actuellement associé
                à aucun menu.
            </p>

        <?php endif; ?>

    </div>


    <form method="POST">

        <button type="submit">
            Confirmer la suppression
        </button>


        <a
            class="retour"
            href="admin-plats.php"
        >
            Annuler
        </a>

    </form>

</main>

</body>

</html>