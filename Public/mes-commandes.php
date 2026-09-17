<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];


// 2. Récupérer les commandes de l'utilisateur
$sql = "
    SELECT
        menus.titre,
        commandes.id,
        commandes.nb_personnes,
        commandes.prix_total,
        commandes.statut,
        commandes.date_prestation,
        commandes.heure_prestation,
        commandes.lieu_prestation,
        commandes.adresse_prestation,
        commandes.frais_livraison,
        commandes.remise_pourcentage,
        commandes.created_at
    FROM commandes

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    WHERE commandes.user_id = :user_id

    ORDER BY commandes.created_at DESC
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':user_id' => $user_id
]);

$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);


// 3. Récupérer l'historique des statuts
$sqlHistorique = "
    SELECT
        historique_statuts.commande_id,
        historique_statuts.statut,
        historique_statuts.date_modification
    FROM historique_statuts

    INNER JOIN commandes
        ON historique_statuts.commande_id = commandes.id

    WHERE commandes.user_id = :user_id

    ORDER BY historique_statuts.date_modification ASC
";

$stmtHistorique = $pdo->prepare($sqlHistorique);

$stmtHistorique->execute([
    ':user_id' => $user_id
]);

$historiques = $stmtHistorique->fetchAll(PDO::FETCH_ASSOC);


// 4. Regrouper l'historique par commande
$historiquesParCommande = [];

foreach ($historiques as $historique) {

    $commandeId = $historique['commande_id'];

    $historiquesParCommande[$commandeId][] = $historique;
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

    <title>Mes commandes - Vite & Gourmand</title>

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
            margin-top: 10px;

            border-radius: 5px;

            display: inline-block;

            background: #eeeeee;
        }

        .actions {
            margin-top: 15px;
        }

        .actions a {
            display: inline-block;

            margin-top: 10px;
            margin-right: 10px;

            text-decoration: none;

            background: black;
            color: white;

            padding: 8px 12px;

            border-radius: 5px;
        }

        .actions a:hover {
            background: #444;
        }

        .historique {
            margin-top: 20px;

            padding: 15px;

            background: #f8f8f8;

            border-radius: 8px;
        }

        .historique ul {
            padding-left: 20px;
        }

        .historique li {
            margin-bottom: 8px;
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
                Bienvenue
                <?php
                echo htmlspecialchars(
                    $_SESSION['email'] ?? ''
                );
                ?>
                👋
            </p>

        </div>


        <div>

            <a href="index.php">
                Accueil
            </a>

            <a href="mes-commandes.php">
                Mes commandes
            </a>

        </div>

    </nav>


    <main>

        <h1>Mes commandes</h1>


        <?php if (empty($commandes)): ?>

            <p>
                Aucune commande pour le moment.
            </p>

        <?php else: ?>


            <?php foreach ($commandes as $commande): ?>

                <div class="commande">

                    <h2>
                        <?php
                        echo htmlspecialchars(
                            $commande['titre']
                        );
                        ?>
                    </h2>


                    <p>
                        Numéro de commande :
                        <?php echo (int) $commande['id']; ?>
                    </p>


                    <p>
                        Nombre de personnes :
                        <?php
                        echo (int) $commande['nb_personnes'];
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


                    <p>
                        Date de prestation :
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
                        Adresse :
                        <?php
                        echo !empty($commande['adresse_prestation'])
                            ? htmlspecialchars(
                                $commande['adresse_prestation']
                            )
                            : 'Non renseignée';
                        ?>
                    </p>


                    <p>
                        Frais de livraison :
                        <?php
                        echo number_format(
                            $commande['frais_livraison'],
                            2,
                            ',',
                            ' '
                        );
                        ?>
                        €
                    </p>


                    <p>
                        Remise :
                        <?php
                        echo number_format(
                            $commande['remise_pourcentage'],
                            0
                        );
                        ?>
                        %
                    </p>


                    <p>
                        Créée le :
                        <?php
                        echo htmlspecialchars(
                            $commande['created_at']
                        );
                        ?>
                    </p>


                    <p class="statut">

                        Statut actuel :

                        <?php
                        echo htmlspecialchars(
                            $commande['statut']
                        );
                        ?>

                    </p>


                    <!-- Historique des statuts -->

                    <div class="historique">

                        <h3>
                            Suivi de la commande
                        </h3>


                        <?php
                        $commandeId = $commande['id'];
                        ?>


                        <?php
                        if (
                            !empty(
                                $historiquesParCommande[$commandeId]
                            )
                        ):
                        ?>

                            <ul>

                                <?php
                                foreach (
                                    $historiquesParCommande[$commandeId]
                                    as $historique
                                ):
                                ?>

                                    <li>

                                        <strong>
                                            <?php
                                            echo htmlspecialchars(
                                                $historique['statut']
                                            );
                                            ?>
                                        </strong>

                                        —

                                        <?php
                                        echo htmlspecialchars(
                                            $historique[
                                                'date_modification'
                                            ]
                                        );
                                        ?>

                                    </li>

                                <?php endforeach; ?>

                            </ul>


                        <?php else: ?>

                            <p>
                                Aucun changement de statut
                                pour le moment.
                            </p>

                        <?php endif; ?>

                    </div>


                    <!-- Actions autorisées uniquement
                    tant que la commande est en attente -->

                    <?php
                    if ($commande['statut'] === 'en attente'):
                    ?>

                        <div class="actions">

                            <a
                                href="modifier-commande.php?id=<?php
                                echo (int) $commande['id'];
                                ?>"
                            >
                                Modifier la commande
                            </a>


                            <a
                                href="supprimer-commande.php?id=<?php
                                echo (int) $commande['id'];
                                ?>"
                            >
                                Annuler la commande
                            </a>

                        </div>


                    <?php else: ?>

                        <p>
                            Cette commande ne peut plus
                            être modifiée ou annulée en ligne.
                        </p>

                    <?php endif; ?>

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