<?php

require_once '../Config/database.php';


// 1. Récupérer les menus
$sql = "
    SELECT *
    FROM menus
    WHERE actif = 1
";

$stmt = $pdo->query($sql);
$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);


// 2. Récupérer uniquement les avis validés
$sqlAvis = "
    SELECT
        avis.note,
        avis.commentaire,
        avis.created_at,
        users.prenom
    FROM avis

    INNER JOIN users
        ON avis.user_id = users.id

    WHERE avis.statut_validation = 'validé'

    ORDER BY avis.created_at DESC

    LIMIT 6
";

$stmtAvis = $pdo->prepare($sqlAvis);
$stmtAvis->execute();

$avisValides = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);

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
        Vite & Gourmand
    </title>

    <link
        rel="stylesheet"
        href="style.css"
    >

    <style>

        .section-filtres {
            padding: 30px 20px;
            text-align: center;
        }

        .section-filtres h2 {
            margin-bottom: 20px;
        }

        #search-menu {
            width: 100%;
            max-width: 500px;

            padding: 12px;

            margin-bottom: 15px;

            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .filters {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;

            gap: 10px;
        }

        .filters input,
        .filters select {
            padding: 10px;

            border: 1px solid #ccc;
            border-radius: 6px;
        }


        /* Avis clients */

        .avis-clients {
            padding: 50px 20px;

            background-color: #f5f5f5;

            text-align: center;
        }

        .avis-clients h2 {
            margin-bottom: 30px;
        }

        .avis-liste {
            display: grid;

            grid-template-columns:
                repeat(
                    auto-fit,
                    minmax(250px, 1fr)
                );

            gap: 20px;

            max-width: 1100px;

            margin: auto;
        }

        .avis-client {
            background-color: white;

            padding: 20px;

            border-radius: 10px;

            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.08);

            text-align: left;
        }

        .avis-client h3 {
            margin-top: 0;
        }

        .avis-note {
            font-size: 20px;

            margin: 10px 0;
        }

        .avis-commentaire {
            line-height: 1.5;
        }


        /* Footer */

        footer {
            padding: 30px 20px;

            text-align: center;

            background-color: #111;
            color: white;
        }

        footer a {
            color: white;
        }


        @media (max-width: 768px) {

            .filters {
                flex-direction: column;
            }

            .filters input,
            .filters select {
                width: 100%;
                box-sizing: border-box;
            }

        }

    </style>

</head>


<body>


    <!-- Navigation -->

    <nav class="navbar">

        <div class="navbar-logo">

            <a href="index.php">

                <img
                    src="LogoVG.png"
                    alt="Logo Vite & Gourmand"
                    width="150"
                >

            </a>


            <h1>

                <a href="index.php">
                    Vite & Gourmand
                </a>

            </h1>

        </div>


        <div class="navbar-links">

            <a href="index.php#menus">
                Menus
            </a>

            <a href="mes-commandes.php">
                Mes commandes
            </a>

            <a href="login.php">
                Connexion
            </a>

        </div>


        <button
            class="burger-menu"
            aria-label="Ouvrir le menu"
            type="button"
        >
            ☰
        </button>

    </nav>


    <!-- Hero -->

    <section class="hero">

        <div class="texte">

            <h1>
                Des plats gourmands,
                prêts sans attendre
            </h1>

            <p>
                Commandez simplement vos menus
                et profitez d'un moment convivial.
            </p>


            <a href="index.php#menus">
                Découvrir nos menus
            </a>

        </div>


        <div class="image">

            <img
                src="Burger.webp"
                alt="Plat gourmand proposé par Vite & Gourmand"
            >

        </div>

    </section>


    <!-- Recherche et filtres -->

    <section class="section-filtres">

        <h2>
            Trouvez votre menu
        </h2>


        <input
            type="text"
            id="search-menu"
            placeholder="Rechercher un menu..."
            aria-label="Rechercher un menu"
        >


        <div class="filters">


            <input
                type="number"
                id="prix-min"
                placeholder="Prix minimum"
                min="0"
                aria-label="Prix minimum"
            >


            <input
                type="number"
                id="prix-max"
                placeholder="Prix maximum"
                min="0"
                aria-label="Prix maximum"
            >


            <select
                id="theme"
                aria-label="Filtrer par thème"
            >

                <option value="">
                    Tous les thèmes
                </option>

                <option value="Classique">
                    Classique
                </option>

                <option value="Noël">
                    Noël
                </option>

            </select>


            <select
                id="regime"
                aria-label="Filtrer par régime alimentaire"
            >

                <option value="">
                    Tous les régimes
                </option>

                <option value="Classique">
                    Classique
                </option>

                <option value="Vegan">
                    Vegan
                </option>

            </select>


            <input
                type="number"
                id="personnes"
                placeholder="Nombre de personnes"
                min="1"
                aria-label="Nombre de personnes"
            >

        </div>

    </section>


    <!-- Menus -->

    <section id="menus">

        <?php if (empty($menus)): ?>

            <p>
                Aucun menu disponible pour le moment.
            </p>


        <?php else: ?>


            <?php foreach ($menus as $menu): ?>

                <div>

                    <h2>

                        <?php
                        echo htmlspecialchars(
                            $menu['titre']
                        );
                        ?>

                    </h2>


                    <h3>

                        <?php
                        echo number_format(
                            $menu['prix'],
                            2,
                            ',',
                            ' '
                        );
                        ?>

                        €

                    </h3>


                    <p>

                        <?php
                        echo htmlspecialchars(
                            $menu['description']
                        );
                        ?>

                    </p>


                    <p>

                        Minimum :

                        <?php
                        echo (int) $menu['nb_personnes_min'];
                        ?>

                        personnes

                    </p>


                    <a
                        href="menu.php?id=<?php
                        echo (int) $menu['id'];
                        ?>"
                    >
                        Afficher le menu
                    </a>

                </div>

            <?php endforeach; ?>


        <?php endif; ?>

    </section>


    <!-- Avis clients -->

    <section class="avis-clients">

        <h2>
            Avis de nos clients
        </h2>


        <?php if (empty($avisValides)): ?>

            <p>
                Aucun avis client pour le moment.
            </p>


        <?php else: ?>

            <div class="avis-liste">


                <?php foreach ($avisValides as $avis): ?>

                    <article class="avis-client">


                        <h3>

                            <?php
                            echo htmlspecialchars(
                                $avis['prenom']
                            );
                            ?>

                        </h3>


                        <p
                            class="avis-note"
                            aria-label="<?php
                            echo (int) $avis['note'];
                            ?> étoiles sur 5"
                        >

                            <?php
                            echo str_repeat(
                                '⭐',
                                (int) $avis['note']
                            );
                            ?>

                        </p>


                        <p class="avis-commentaire">

                            <?php
                            echo nl2br(
                                htmlspecialchars(
                                    $avis['commentaire']
                                )
                            );
                            ?>

                        </p>

                    </article>

                <?php endforeach; ?>


            </div>

        <?php endif; ?>

    </section>


    <!-- Footer -->

    <footer>

        <p>
            © 2026 Vite & Gourmand
            - Tous droits réservés
        </p>

    </footer>


    <script src="scripts.js?v=2"></script>

</body>

</html>