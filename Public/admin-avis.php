<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// 2. Autoriser uniquement admin et employé
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// 3. Récupérer tous les avis
$sql = "
    SELECT
        avis.id,
        avis.note,
        avis.commentaire,
        avis.statut_validation,
        avis.created_at,
        users.nom,
        users.prenom,
        users.email,
        menus.titre
    FROM avis

    INNER JOIN users
        ON avis.user_id = users.id

    INNER JOIN commandes
        ON avis.commande_id = commandes.id

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    ORDER BY avis.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$avis = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Gestion des avis - Vite & Gourmand
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }

        h1 {
            color: #333;
        }

        .navbar {
            background-color: #111;
            color: white;

            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;

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

        .avis {
            background-color: white;

            padding: 20px;
            margin-bottom: 20px;

            border-radius: 10px;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .note {
            font-size: 20px;
            font-weight: bold;
        }

        .statut {
            display: inline-block;

            margin-top: 10px;
            padding: 6px 10px;

            background-color: #eeeeee;

            border-radius: 5px;

            font-weight: bold;
        }

        .actions {
            margin-top: 15px;
        }

        .actions a {
            display: inline-block;

            text-decoration: none;

            padding: 8px 14px;
            margin-right: 10px;

            border-radius: 5px;

            color: white;
        }

        .valider {
            background-color: green;
        }

        .refuser {
            background-color: #b00020;
        }

        .actions a:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {

            .navbar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .actions a {
                display: block;
                margin-top: 10px;
                margin-right: 0;
            }
        }

    </style>

</head>


<body>

    <nav class="navbar">

        <div>
            <strong>
                Vite & Gourmand
            </strong>
        </div>


        <div>

            <a href="admin-commandes.php">
                Commandes
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
            Gestion des avis clients
        </h1>


        <?php if (empty($avis)): ?>

            <p>
                Aucun avis pour le moment.
            </p>


        <?php else: ?>


            <?php foreach ($avis as $unAvis): ?>

                <div class="avis">

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $unAvis['titre']
                        );
                        ?>
                    </h2>


                    <p>
                        Client :
                        <strong>
                            <?php
                            echo htmlspecialchars(
                                $unAvis['prenom']
                            );
                            ?>

                            <?php
                            echo htmlspecialchars(
                                $unAvis['nom']
                            );
                            ?>
                        </strong>
                    </p>


                    <p>
                        Email :
                        <?php
                        echo htmlspecialchars(
                            $unAvis['email']
                        );
                        ?>
                    </p>


                    <p class="note">

                        Note :

                        <?php
                        echo (int) $unAvis['note'];
                        ?>

                        / 5 ⭐

                    </p>


                    <p>
                        Commentaire :
                    </p>


                    <p>
                        <?php
                        echo nl2br(
                            htmlspecialchars(
                                $unAvis['commentaire']
                            )
                        );
                        ?>
                    </p>


                    <p>
                        Avis envoyé le :
                        <?php
                        echo htmlspecialchars(
                            $unAvis['created_at']
                        );
                        ?>
                    </p>


                    <p class="statut">

                        Statut :

                        <?php
                        echo htmlspecialchars(
                            $unAvis['statut_validation']
                        );
                        ?>

                    </p>


                    <?php
                    if (
                        $unAvis['statut_validation']
                        === 'en attente'
                    ):
                    ?>

                        <div class="actions">

                            <a
                                class="valider"
                                href="changer-avis.php?id=<?php
                                echo (int) $unAvis['id'];
                                ?>&statut=validé"
                            >
                                Valider
                            </a>


                            <a
                                class="refuser"
                                href="changer-avis.php?id=<?php
                                echo (int) $unAvis['id'];
                                ?>&statut=refusé"
                            >
                                Refuser
                            </a>

                        </div>

                    <?php else: ?>

                        <p>
                            Cet avis a déjà été traité.
                        </p>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>


        <?php endif; ?>

    </main>

</body>

</html>
