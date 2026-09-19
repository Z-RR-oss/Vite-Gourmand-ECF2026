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


// 3. Récupérer les filtres
$client = trim($_GET['client'] ?? '');
$statut = trim($_GET['statut'] ?? '');


// 4. Construire la requête
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
";

$conditions = [];
$params = [];


// Filtre client
if ($client !== '') {

    $conditions[] = "
        (
            users.nom LIKE :client
            OR users.prenom LIKE :client
            OR users.email LIKE :client
        )
    ";

    $params[':client'] = '%' . $client . '%';
}


// Filtre statut
if ($statut !== '') {

    $conditions[] = "
        commandes.statut = :statut
    ";

    $params[':statut'] = $statut;
}


// Ajouter WHERE si nécessaire
if (!empty($conditions)) {

    $sql .= "
        WHERE "
        . implode(
            " AND ",
            $conditions
        );
}


$sql .= "
    ORDER BY commandes.created_at DESC
";


// 5. Exécuter la requête
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

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

    <title>
        Gestion des commandes - Vite & Gourmand
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


        /* Navigation */

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


        /* Filtres */

        .filtres {

            background: white;

            padding: 20px;

            margin-bottom: 25px;

            border-radius: 10px;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.08);
        }

        .filtres form {

            display: flex;

            flex-wrap: wrap;

            gap: 10px;

            align-items: end;
        }

        .champ {

            display: flex;

            flex-direction: column;

            gap: 5px;
        }

        .champ label {
            font-weight: bold;
        }

        .champ input,
        .champ select {

            padding: 10px;

            border: 1px solid #ccc;

            border-radius: 5px;
        }

        .filtres button {

            padding: 10px 15px;

            border: none;

            border-radius: 5px;

            background: black;

            color: white;

            cursor: pointer;
        }

        .filtres button:hover {
            background: #444;
        }

        .reset {

            display: inline-block;

            padding: 10px 15px;

            text-decoration: none;

            background: #ddd;

            color: black;

            border-radius: 5px;
        }


        /* Commandes */

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


        /* Actions */

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

        .actions .annuler {

            background: #b00020;
        }

        .actions .annuler:hover {

            background: #800018;
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

            .filtres form {

                flex-direction: column;

                align-items: stretch;
            }

            .champ input,
            .champ select {

                width: 100%;

                box-sizing: border-box;
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


    <!-- Navigation -->

    <nav class="navbar">

        <div>

            <h2>
                🍽️ Vite & Gourmand
            </h2>

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

            <a href="admin-commandes.php">
                Gestion commandes
            </a>

            <a href="admin-avis.php">
                Gestion avis
            </a>

        </div>

    </nav>


    <main>


        <h1>
            Gestion des commandes
        </h1>


        <!-- Filtres -->

        <section class="filtres">

            <h2>
                Rechercher une commande
            </h2>


            <form
                method="GET"
                action="admin-commandes.php"
            >


                <div class="champ">

                    <label for="client">
                        Client
                    </label>

                    <input
                        type="text"
                        id="client"
                        name="client"
                        placeholder="Nom, prénom ou email"
                        value="<?php
                        echo htmlspecialchars(
                            $client
                        );
                        ?>"
                    >

                </div>


                <div class="champ">

                    <label for="statut">
                        Statut
                    </label>

                    <select
                        id="statut"
                        name="statut"
                    >

                        <option value="">
                            Tous les statuts
                        </option>


                        <option
                            value="en attente"
                            <?php
                            if ($statut === 'en attente') {
                                echo 'selected';
                            }
                            ?>
                        >
                            En attente
                        </option>


                        <option
                            value="accepté"
                            <?php
                            if ($statut === 'accepté') {
                                echo 'selected';
                            }
                            ?>
                        >
                            Accepté
                        </option>


                        <option
                            value="en préparation"
                            <?php
                            if ($statut === 'en préparation') {
                                echo 'selected';
                            }
                            ?>
                        >
                            En préparation
                        </option>


                        <option
                            value="en cours de livraison"
                            <?php
                            if (
                                $statut ===
                                'en cours de livraison'
                            ) {
                                echo 'selected';
                            }
                            ?>
                        >
                            En cours de livraison
                        </option>


                        <option
                            value="livré"
                            <?php
                            if ($statut === 'livré') {
                                echo 'selected';
                            }
                            ?>
                        >
                            Livré
                        </option>


                        <option
                            value="en attente du retour de matériel"
                            <?php
                            if (
                                $statut ===
                                'en attente du retour de matériel'
                            ) {
                                echo 'selected';
                            }
                            ?>
                        >
                            En attente du retour de matériel
                        </option>


                        <option
                            value="terminée"
                            <?php
                            if ($statut === 'terminée') {
                                echo 'selected';
                            }
                            ?>
                        >
                            Terminée
                        </option>


                        <option
                            value="annulée"
                            <?php
                            if ($statut === 'annulée') {
                                echo 'selected';
                            }
                            ?>
                        >
                            Annulée
                        </option>

                    </select>

                </div>


                <button type="submit">
                    Filtrer
                </button>


                <a
                    class="reset"
                    href="admin-commandes.php"
                >
                    Réinitialiser
                </a>

            </form>

        </section>


        <!-- Résultats -->

        <?php if (empty($commandes)): ?>

            <p>
                Aucune commande ne correspond à votre recherche.
            </p>


        <?php else: ?>


            <p>

                <?php
                echo count($commandes);
                ?>

                commande(s) trouvée(s).

            </p>


            <?php foreach ($commandes as $commande): ?>


                <div class="commande">


                    <h2>

                        Commande n°

                        <?php
                        echo (int) $commande['id'];
                        ?>

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
                        echo (int)
                            $commande['nb_personnes'];
                        ?>

                    </p>


                    <p>

                        Date :

                        <?php
                        echo !empty(
                            $commande['date_prestation']
                        )
                            ? htmlspecialchars(
                                $commande['date_prestation']
                            )
                            : 'Non renseignée';
                        ?>

                    </p>


                    <p>

                        Heure :

                        <?php
                        echo !empty(
                            $commande['heure_prestation']
                        )
                            ? htmlspecialchars(
                                $commande['heure_prestation']
                            )
                            : 'Non renseignée';
                        ?>

                    </p>


                    <p>

                        Lieu :

                        <?php
                        echo !empty(
                            $commande['lieu_prestation']
                        )
                            ? htmlspecialchars(
                                $commande['lieu_prestation']
                            )
                            : 'Non renseigné';
                        ?>

                    </p>


                    <p>

                        Adresse :

                        <?php
                        echo !empty(
                            $commande['adresse_prestation']
                        )
                            ? htmlspecialchars(
                                $commande['adresse_prestation']
                            )
                            : 'Non renseignée';
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


                    <?php
                    if (
                        $commande['statut'] !== 'terminée'
                        && $commande['statut'] !== 'annulée'
                    ):
                    ?>


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
                                echo urlencode(
                                    'en préparation'
                                );
                                ?>"
                            >
                                Préparer
                            </a>


                            <a
                                href="changer-statut.php?id=<?php
                                echo (int) $commande['id'];
                                ?>&statut=<?php
                                echo urlencode(
                                    'en cours de livraison'
                                );
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


                            <a
                                class="annuler"
                                href="annuler-commande-employe.php?id=<?php
                                echo (int) $commande['id'];
                                ?>"
                            >
                                Annuler la commande
                            </a>


                        </div>


                    <?php endif; ?>


                    <?php
                    if ($commande['statut'] === 'annulée'):
                    ?>

                        <p>

                            <strong>
                                Commande annulée.
                            </strong>

                        </p>

                        <?php
                        if (
                            !empty(
                                $commande[
                                    'mode_contact_annulation'
                                ]
                            )
                        ):
                        ?>

                            <p>

                                Contact client :

                                <?php
                                echo htmlspecialchars(
                                    $commande[
                                        'mode_contact_annulation'
                                    ]
                                );
                                ?>

                            </p>

                        <?php endif; ?>


                        <?php
                        if (
                            !empty(
                                $commande[
                                    'motif_annulation'
                                ]
                            )
                        ):
                        ?>

                            <p>

                                Motif :

                                <?php
                                echo htmlspecialchars(
                                    $commande[
                                        'motif_annulation'
                                    ]
                                );
                                ?>

                            </p>

                        <?php endif; ?>


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
