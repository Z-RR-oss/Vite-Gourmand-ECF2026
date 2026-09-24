<?php

session_start();
require_once '../Config/database.php';


// Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// Autoriser uniquement admin et employé
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// Récupérer les plats + allergènes associés
$sql = "
    SELECT
        plats.id,
        plats.nom,
        plats.description,
        plats.type_plat,

        GROUP_CONCAT(
            DISTINCT allergenes.nom
            ORDER BY allergenes.nom
            SEPARATOR ', '
        ) AS allergenes

    FROM plats

    LEFT JOIN plat_allergene
        ON plats.id = plat_allergene.plat_id

    LEFT JOIN allergenes
        ON plat_allergene.allergene_id = allergenes.id

    GROUP BY
        plats.id,
        plats.nom,
        plats.description,
        plats.type_plat

    ORDER BY plats.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Gestion des plats - Vite & Gourmand
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        .navbar {
            background: #111;
            color: white;

            padding: 15px 20px;
            margin-bottom: 30px;

            border-radius: 10px;

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 10px;
        }

        .navbar a:hover {
            color: orange;
        }

        .header-actions {
            margin-bottom: 25px;
        }

        .bouton {
            display: inline-block;

            padding: 10px 15px;

            background: black;
            color: white;

            text-decoration: none;

            border-radius: 5px;
        }

        .plat {
            background: white;

            padding: 20px;
            margin-bottom: 20px;

            border-radius: 10px;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .type {
            display: inline-block;

            padding: 5px 10px;

            background: #eeeeee;

            border-radius: 5px;

            font-weight: bold;
        }

        .actions {
            margin-top: 15px;
        }

        .actions a {
            display: inline-block;

            margin-right: 10px;
            margin-top: 8px;

            padding: 8px 12px;

            background: black;
            color: white;

            text-decoration: none;

            border-radius: 5px;
        }

        .actions .supprimer {
            background: #b00020;
        }

        @media (max-width: 768px) {

            .navbar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .actions a {
                display: block;
                margin-right: 0;
            }
        }

    </style>

</head>


<body>

<nav class="navbar">

    <div>
        <strong>
            🍽️ Vite & Gourmand
        </strong>
    </div>


    <div>

        <a href="admin-commandes.php">
            Commandes
        </a>

        <a href="admin-menus.php">
            Menus
        </a>

        <a href="admin-plats.php">
            Plats
        </a>

        <a href="admin-avis.php">
            Avis
        </a>

        <a href="index.php">
            Accueil
        </a>

    </div>

</nav>


<main>

    <h1>
        Gestion des plats
    </h1>


    <div class="header-actions">

        <a
            class="bouton"
            href="ajouter-plat.php"
        >
            + Ajouter un plat
        </a>

    </div>


    <?php if (empty($plats)): ?>

        <p>
            Aucun plat enregistré.
        </p>


    <?php else: ?>


        <?php foreach ($plats as $plat): ?>

            <article class="plat">

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $plat['nom']
                    );
                    ?>

                </h2>


                <p class="type">

                    <?php
                    echo htmlspecialchars(
                        $plat['type_plat']
                    );
                    ?>

                </p>


                <p>

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $plat['description'] ?? ''
                        )
                    );
                    ?>

                </p>


                <p>

                    <strong>
                        Allergènes :
                    </strong>

                    <?php
                    echo !empty($plat['allergenes'])
                        ? htmlspecialchars(
                            $plat['allergenes']
                        )
                        : 'Aucun allergène renseigné';
                    ?>

                </p>


                <div class="actions">

                    <a
                        href="modifier-plat.php?id=<?php
                        echo (int) $plat['id'];
                        ?>"
                    >
                        Modifier
                    </a>


                    <a
                        class="supprimer"
                        href="supprimer-plat.php?id=<?php
                        echo (int) $plat['id'];
                        ?>"
                    >
                        Supprimer
                    </a>

                </div>

            </article>

        <?php endforeach; ?>


    <?php endif; ?>

</main>

</body>

</html>