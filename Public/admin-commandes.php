<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// 2. Autoriser uniquement les admins et employés
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// 3. Récupérer toutes les commandes
$sql = "
    SELECT
        commandes.*,
        menus.titre,
        users.email,
        users.nom,
        users.prenom
    FROM commandes

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    INNER JOIN users
        ON commandes.user_id = users.id

    ORDER BY commandes.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Gestion des commandes - Vite & Gourmand</title>

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

        .commande {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.1);

            transition: 0.2s;
        }

        .commande:hover {
            transform: translateY(-3px);
        }

        .statut {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            background: #eeeeee;
        }

        .actions {
            margin-top: 15px;
        }

        .actions a {
            display: inline-block;

            margin-top: 8px;
            margin-right: 8px;

            text-decoration: none;

            background: black;
            color: white;

            padding: 8px 12px;
            border-radius: 5px;
        }

        .actions a:hover {
            background: #444;
        }

        footer {
            text-align: center;
            margin-top: 50px;
            color: gray;
        }

        @media (max-width: 768px) {

            .navbar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }

            .commande {
                padding: 12px;
            }

            .actions a {
                display: block;
                margin-top: 10px;
            }
        }

    </style>

</head>


<body>

    <nav class="navbar">

        <div>

            <h2>🍽️ Vite & Gourmand</h2>

            <p>
                Connecté :
                <?php
                echo htmlspecialchars(
                    $_SESSION['email'] ?? ''
                );
                ?>
            </p>

        </div>


        <div>

            <a href="index.php">
                Accueil
            </a>

            <a href="mes-commandes.php">
                Mes commandes
            </a>

            <a href="admin-commandes.php">
                Gestion commandes
            </a>

        </div>

    </nav>


    <main>

        <h1>Gestion des commandes</h1>


        <?php if (empty($commandes)): ?>

            <p>
                Aucune commande pour le moment.
            </p>

        <?php else: ?>


            <?php foreach ($commandes as $commande): ?>

                <div class="commande">

                    <h2>
                        Commande n°
                        <?php echo (int) $commande['id']; ?>
                    </h2>


                    <h3>
                        Menu :
                        <?php
                        echo htmlspecialchars(
                            $commande['titre']
                        );
                        ?>
                    </h3>


                    <p>
                        Client :
                        <?php
                        echo htmlspecialchars(
                            $commande['prenom']
                            . ' '
                            . $commande['nom']
                        );
                        ?>
                    </p>


                    <p>
                        Email :
                        <?php
                        echo htmlspecialchars(
                            $commande['email']
                        );
                        ?>
                    </p>


                    <p>
                        Nombre de personnes :
                        <?php
                        echo (int) $commande['nb_personnes'];
                        ?>
                    </p>


                    <p>
                        Date :
                        <?php
                        echo !empty($commande['date_prestation'])
                            ? htmlspecialchars(
                                $commande['date_prestation']
                            )
                            : 'Non renseignée';
                        ?>
                    </p>


                    <p>
                        Heure :
                        <?php
                        echo !empty($commande['heure_prestation'])
                            ? htmlspecialchars(
                                $commande['heure_prestation']
                            )
                            : 'Non renseignée';
                        ?>
                    </p>


                    <p>
                        Lieu :
                        <?php
                        echo !empty($commande['lieu_prestation'])
                            ? htmlspecialchars(
                                $commande['lieu_prestation']
                            )
                            : 'Non renseigné';
                        ?>
                    </p>


                    <p>
                        Prix total :
                        <?php
                        echo number_format(
                            $commande['prix_total'],
                            2,
                            ',',
                            ' '
                        );
                        ?>
                        €
                    </p>


                    <p class="statut">
                        Statut :
                        <?php
                        echo htmlspecialchars(
                            $commande['statut']
                        );
                        ?>
                    </p>


                    <div class="actions">

                        <a
                            href="changer-statut.php?id=<?php
                            echo (int) $commande['id'];
                            ?>&statut=<?php
                            echo urlencode('accepté');
                            ?>"
                        >
                            Accepter
                        </a>


                        <a
                            href="changer-statut.php?id=<?php
                            echo (int) $commande['id'];
                            ?>&statut=<?php
                            echo urlencode('en préparation');
                            ?>"
                        >
                            Préparer
                        </a>


                        <a
                            href="changer-statut.php?id=<?php
                            echo (int) $commande['id'];
                            ?>&statut=<?php
                            echo urlencode('en cours de livraison');
                            ?>"
                        >
                            En cours de livraison
                        </a>


                        <a
                            href="changer-statut.php?id=<?php
                            echo (int) $commande['id'];
                            ?>&statut=<?php
                            echo urlencode('livré');
                            ?>"
                        >
                            Livré
                        </a>


                        <a
                            href="changer-statut.php?id=<?php
                            echo (int) $commande['id'];
                            ?>&statut=<?php
                            echo urlencode(
                                'en attente du retour de matériel'
                            );
                            ?>"
                        >
                            Attente retour matériel
                        </a>


                        <a
                            href="changer-statut.php?id=<?php
                            echo (int) $commande['id'];
                            ?>&statut=<?php
                            echo urlencode('terminée');
                            ?>"
                        >
                            Terminer
                        </a>

                    </div>

                </div>

            <?php endforeach; ?>


        <?php endif; ?>

    </main>


    <footer>

        <p>
            © 2026 Vite & Gourmand
            - Tous droits réservés
        </p>

    </footer>

</body>

</html>

