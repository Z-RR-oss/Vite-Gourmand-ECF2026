<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// 2. Autoriser admin / employé
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// 3. Vérifier l'identifiant du menu
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit("ID de menu invalide.");
}

$id = (int) $id;


// 4. Récupérer le menu
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


// 5. Récupérer tous les plats
$sqlPlats = "
    SELECT
        id,
        nom,
        description,
        type_plat
    FROM plats

    ORDER BY
        FIELD(
            type_plat,
            'entree',
            'plat',
            'dessert'
        ),
        nom ASC
";

$stmtPlats = $pdo->prepare($sqlPlats);
$stmtPlats->execute();

$plats = $stmtPlats->fetchAll(PDO::FETCH_ASSOC);


// 6. Récupérer les plats déjà associés au menu
$sqlPlatsMenu = "
    SELECT
        plat_id,
        ordre_affichage
    FROM menu_plat
    WHERE menu_id = :menu_id
";

$stmtPlatsMenu = $pdo->prepare(
    $sqlPlatsMenu
);

$stmtPlatsMenu->execute([
    ':menu_id' => $id
]);

$platsMenu = $stmtPlatsMenu->fetchAll(
    PDO::FETCH_ASSOC
);


// Tableau pratique :
// [plat_id => ordre_affichage]
$platsSelectionnes = [];

foreach ($platsMenu as $platMenu) {

    $platsSelectionnes[
        $platMenu['plat_id']
    ] = $platMenu['ordre_affichage'];
}


$erreur = '';


// 7. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $platsChoisis =
        $_POST['plats'] ?? [];

    $ordres =
        $_POST['ordre'] ?? [];


    try {

        $pdo->beginTransaction();


        // 8. Supprimer les anciennes associations
        $sqlDelete = "
            DELETE FROM menu_plat
            WHERE menu_id = :menu_id
        ";

        $stmtDelete = $pdo->prepare(
            $sqlDelete
        );

        $stmtDelete->execute([
            ':menu_id' => $id
        ]);


        // 9. Enregistrer les nouvelles associations
        foreach ($platsChoisis as $platId) {

            if (!is_numeric($platId)) {
                continue;
            }

            $platId = (int) $platId;


            $ordre = isset($ordres[$platId])
                ? (int) $ordres[$platId]
                : 0;


            if ($ordre < 0) {
                $ordre = 0;
            }


            $sqlInsert = "
                INSERT INTO menu_plat (
                    menu_id,
                    plat_id,
                    ordre_affichage
                )

                VALUES (
                    :menu_id,
                    :plat_id,
                    :ordre_affichage
                )
            ";

            $stmtInsert = $pdo->prepare(
                $sqlInsert
            );

            $stmtInsert->execute([
                ':menu_id' => $id,
                ':plat_id' => $platId,
                ':ordre_affichage' => $ordre
            ]);
        }


        $pdo->commit();


        header(
            "Location: admin-menus.php"
        );

        exit;


    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }


        error_log(
            "Erreur association menu/plats : "
            . $e->getMessage()
        );


        $erreur =
            "Une erreur est survenue lors de l'enregistrement.";
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
        Composition du menu - Vite & Gourmand
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        main {
            max-width: 800px;
            margin: auto;

            background: white;

            padding: 25px;

            border-radius: 10px;

            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .plat {
            background: #f7f7f7;

            padding: 15px;

            margin-bottom: 12px;

            border-radius: 8px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 20px;
        }

        .plat-infos {
            flex: 1;
        }

        .type {
            display: inline-block;

            padding: 4px 8px;

            background: #ddd;

            border-radius: 5px;

            font-size: 14px;
        }

        .selection {
            display: flex;
            align-items: center;

            gap: 10px;
        }

        .ordre {
            width: 70px;

            padding: 7px;

            border: 1px solid #ccc;

            border-radius: 5px;
        }

        button {
            margin-top: 20px;

            padding: 10px 18px;

            border: none;

            border-radius: 5px;

            background: black;
            color: white;

            cursor: pointer;
        }

        button:hover {
            background: #444;
        }

        .erreur {
            background: #ffdede;

            color: #8b0000;

            padding: 10px;

            border-radius: 5px;
        }

        .retour {
            display: inline-block;

            margin-top: 20px;
        }

        @media (max-width: 700px) {

            .plat {
                flex-direction: column;
                align-items: flex-start;
            }

        }

    </style>

</head>


<body>

<main>

    <h1>
        Composition du menu
    </h1>


    <h2>

        <?php
        echo htmlspecialchars(
            $menu['titre']
        );
        ?>

    </h2>


    <p>
        Sélectionne les plats qui composent ce menu.
        Le numéro d'ordre détermine leur ordre d'affichage.
    </p>


    <?php if ($erreur !== ''): ?>

        <p class="erreur">

            <?php
            echo htmlspecialchars(
                $erreur
            );
            ?>

        </p>

    <?php endif; ?>


    <form method="POST">


        <?php if (empty($plats)): ?>

            <p>
                Aucun plat disponible.
                Crée d'abord des plats dans la gestion des plats.
            </p>


        <?php else: ?>


            <?php foreach ($plats as $plat): ?>

                <?php

                $platId = (int) $plat['id'];

                $estSelectionne =
                    array_key_exists(
                        $platId,
                        $platsSelectionnes
                    );

                $ordreActuel =
                    $platsSelectionnes[$platId]
                    ?? 0;

                ?>


                <div class="plat">


                    <div class="plat-infos">

                        <strong>

                            <?php
                            echo htmlspecialchars(
                                $plat['nom']
                            );
                            ?>

                        </strong>


                        <p class="type">

                            <?php

                            if ($plat['type_plat'] === 'entree') {
                                echo 'Entrée';
                            } elseif ($plat['type_plat'] === 'dessert') {
                                echo 'Dessert';
                            } else {
                                echo 'Plat';
                            }

                            ?>

                        </p>


                        <?php
                        if (!empty($plat['description'])):
                        ?>

                            <p>

                                <?php
                                echo htmlspecialchars(
                                    $plat['description']
                                );
                                ?>

                            </p>

                        <?php endif; ?>

                    </div>


                    <div class="selection">


                        <label>

                            <input
                                type="checkbox"
                                name="plats[]"
                                value="<?php
                                echo $platId;
                                ?>"
                                <?php
                                if ($estSelectionne) {
                                    echo 'checked';
                                }
                                ?>
                            >

                            Sélectionner

                        </label>


                        <label>

                            Ordre :

                            <input
                                class="ordre"
                                type="number"
                                name="ordre[<?php
                                echo $platId;
                                ?>]"
                                min="0"
                                value="<?php
                                echo (int) $ordreActuel;
                                ?>"
                            >

                        </label>


                    </div>


                </div>


            <?php endforeach; ?>


        <?php endif; ?>


        <?php if (!empty($plats)): ?>

            <button type="submit">
                Enregistrer la composition
            </button>

        <?php endif; ?>


    </form>


    <a
        class="retour"
        href="admin-menus.php"
    >
        Retour aux menus
    </a>

</main>

</body>

</html>