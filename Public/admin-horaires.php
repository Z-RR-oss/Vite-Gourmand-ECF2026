<?php

session_start();
require_once '../Config/database.php';


// Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// Autoriser uniquement admin / employé
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// Les 7 jours de la semaine
$jours = [
    'Lundi',
    'Mardi',
    'Mercredi',
    'Jeudi',
    'Vendredi',
    'Samedi',
    'Dimanche'
];


// Récupérer les horaires existants
$sql = "
    SELECT *
    FROM horaires
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$horairesBdd = $stmt->fetchAll(
    PDO::FETCH_ASSOC
);


// Regrouper les horaires par jour
$horaires = [];

foreach ($horairesBdd as $horaire) {

    $horaires[
        $horaire['jour']
    ] = $horaire;
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
        Gestion des horaires - Vite & Gourmand
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

        .horaire {
            background: white;

            padding: 20px;

            margin-bottom: 15px;

            border-radius: 10px;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.1);

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 20px;
        }

        .infos {
            flex: 1;
        }

        .ferme {
            display: inline-block;

            padding: 5px 10px;

            background: #f5dddd;

            border-radius: 5px;

            font-weight: bold;
        }

        .ouvert {
            display: inline-block;

            padding: 5px 10px;

            background: #e3f5e3;

            border-radius: 5px;

            font-weight: bold;
        }

        .action {
            display: inline-block;

            padding: 8px 12px;

            background: black;
            color: white;

            text-decoration: none;

            border-radius: 5px;
        }

        .action:hover {
            background: #444;
        }

        @media (max-width: 700px) {

            .navbar {
                flex-direction: column;

                gap: 10px;

                text-align: center;
            }

            .horaire {
                flex-direction: column;

                align-items: flex-start;
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

        <a href="admin-horaires.php">
            Horaires
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
        Gestion des horaires
    </h1>


    <p>
        Définissez les horaires d'ouverture
        pour chaque jour de la semaine.
    </p>


    <?php foreach ($jours as $jour): ?>

        <?php
        $horaire = $horaires[$jour] ?? null;
        ?>


        <section class="horaire">


            <div class="infos">

                <h2>

                    <?php
                    echo htmlspecialchars(
                        $jour
                    );
                    ?>

                </h2>


                <?php if (!$horaire): ?>

                    <p>
                        Aucun horaire renseigné.
                    </p>


                <?php elseif ((int) $horaire['ferme'] === 1): ?>

                    <span class="ferme">
                        Fermé
                    </span>


                <?php else: ?>

                    <span class="ouvert">
                        Ouvert
                    </span>


                    <p>

                        <?php
                        echo htmlspecialchars(
                            substr(
                                $horaire['heure_ouverture'],
                                0,
                                5
                            )
                        );
                        ?>

                        -

                        <?php
                        echo htmlspecialchars(
                            substr(
                                $horaire['heure_fermeture'],
                                0,
                                5
                            )
                        );
                        ?>

                    </p>

                <?php endif; ?>

            </div>


            <div>

                <a
                    class="action"
                    href="modifier-horaire.php?jour=<?php
                    echo urlencode($jour);
                    ?>"
                >

                    <?php
                    echo $horaire
                        ? 'Modifier'
                        : 'Ajouter';
                    ?>

                </a>

            </div>


        </section>


    <?php endforeach; ?>


</main>


</body>

</html>