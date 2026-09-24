<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// 2. Autoriser uniquement admin / employé
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// 3. Récupérer les menus
$sql = "
    SELECT *
    FROM menus
    ORDER BY id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Gestion des menus - Vite & Gourmand
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

        .bouton:hover {
            background: #444;
        }

        .menu {
            background: white;

            padding: 20px;
            margin-bottom: 20px;

            border-radius: 10px;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .statut-actif {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 5px;

            background: #e3f5e3;
        }

        .statut-inactif {
            display: inline-block;

            padding: 5px 10px;

            border-radius: 5px;

            background: #f5dddd;
        }

        .actions {
            margin-top: 15px;
        }

        .actions a {
            display: inline-block;

            margin-right: 10px;
            margin-top: 8px;

            padding: 8px 12px;

            text-decoration: none;

            border-radius: 5px;

            background: black;
            color: white;
        }

        .actions a:hover {
            background: #444;
        }

        .actions .plats {
            background: #176b3a;
        }

        .actions .plats:hover {
            background: #10502b;
        }

        .actions .supprimer {
            background: #b00020;
        }

        .actions .supprimer:hover {
            background: #800018;
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
        Gestion des menus
    </h1>


    <div class="header-actions">

        <a
            class="bouton"
            href="ajouter-menu.php"
        >
            + Ajouter un menu
        </a>

    </div>


    <?php if (empty($menus)): ?>

        <p>
            Aucun menu enregistré.
        </p>


    <?php else: ?>


        <?php foreach ($menus as $menu): ?>


            <article class="menu">


                <h2>

                    <?php
                    echo htmlspecialchars(
                        $menu['titre']
                    );
                    ?>

                </h2>


                <p>

                    <?php
                    echo nl2br(
                        htmlspecialchars(
                            $menu['description']
                        )
                    );
                    ?>

                </p>


                <p>

                    <strong>Prix :</strong>

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
                        Nombre minimum de personnes :
                    </strong>

                    <?php
                    echo (int)
                        $menu['nb_personnes_min'];
                    ?>

                </p>


                <p>

                    <strong>Thème :</strong>

                    <?php
                    echo htmlspecialchars(
                        $menu['theme']
                    );
                    ?>

                </p>


                <p>

                    <strong>Régime :</strong>

                    <?php
                    echo htmlspecialchars(
                        $menu['regime']
                    );
                    ?>

                </p>


                <p>

                    <strong>
                        Stock disponible :
                    </strong>

                    <?php
                    echo (int)
                        $menu['stock_disponible'];
                    ?>

                </p>


                <p>

                    <strong>
                        Délai de commande :
                    </strong>

                    <?php
                    echo (int)
                        $menu['delai_commande_heures'];
                    ?>

                    heure(s)

                </p>


                <p>

                    <strong>
                        Conditions :
                    </strong>

                    <?php
                    echo !empty(
                        $menu['conditions_menu']
                    )
                        ? htmlspecialchars(
                            $menu['conditions_menu']
                        )
                        : 'Aucune';
                    ?>

                </p>


                <p>

                    <?php if ((int) $menu['actif'] === 1): ?>

                        <span class="statut-actif">
                            Actif
                        </span>

                    <?php else: ?>

                        <span class="statut-inactif">
                            Inactif
                        </span>

                    <?php endif; ?>

                </p>


                <div class="actions">


                    <a
                        href="menu.php?id=<?php
                        echo (int) $menu['id'];
                        ?>"
                    >
                        Voir
                    </a>


                    <a
                        class="plats"
                        href="gerer-menu-plats.php?id=<?php
                        echo (int) $menu['id'];
                        ?>"
                    >
                        Gérer les plats
                    </a>


                    <a
                        href="modifier-menu.php?id=<?php
                        echo (int) $menu['id'];
                        ?>"
                    >
                        Modifier
                    </a>


                    <a
                        class="supprimer"
                        href="supprimer-menu.php?id=<?php
                        echo (int) $menu['id'];
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