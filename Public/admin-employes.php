<?php

session_start();
require_once '../Config/database.php';


// Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// Seul l'administrateur peut gérer les employés
if ($_SESSION['role'] !== 'admin') {
    exit("Accès refusé.");
}


// Récupérer uniquement les comptes employés
$sql = "
    SELECT
        id,
        nom,
        prenom,
        email,
        gsm,
        adresse,
        actif,
        created_at
    FROM users

    WHERE role = 'employe'

    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$employes = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        Gestion des employés - Vite & Gourmand
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;

            background: #f4f4f4;

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

        .ajouter {
            display: inline-block;

            padding: 10px 15px;

            margin-bottom: 25px;

            background: black;

            color: white;

            text-decoration: none;

            border-radius: 5px;
        }

        .employe {
            background: white;

            padding: 20px;

            margin-bottom: 20px;

            border-radius: 10px;

            box-shadow:
                0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .actif {
            display: inline-block;

            padding: 5px 10px;

            background: #e0f3e0;

            border-radius: 5px;

            font-weight: bold;
        }

        .inactif {
            display: inline-block;

            padding: 5px 10px;

            background: #f5dddd;

            border-radius: 5px;

            font-weight: bold;
        }

        button {
            margin-top: 15px;

            padding: 9px 14px;

            border: none;

            border-radius: 5px;

            cursor: pointer;

            font-weight: bold;
        }

        .desactiver {
            background: #b00020;

            color: white;
        }

        .activer {
            background: #176b3a;

            color: white;
        }

        @media (max-width: 700px) {

            .navbar {
                flex-direction: column;

                gap: 10px;

                text-align: center;
            }

        }

    </style>

</head>


<body>


<nav class="navbar">

    <strong>
        🍽️ Vite & Gourmand
    </strong>


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

        <a href="admin-employes.php">
            Employés
        </a>

        <a href="index.php">
            Accueil
        </a>

    </div>

</nav>


<main>

    <h1>
        Gestion des employés
    </h1>


    <a
        class="ajouter"
        href="ajouter-employe.php"
    >
        + Créer un compte employé
    </a>


    <?php if (empty($employes)): ?>

        <p>
            Aucun compte employé.
        </p>

    <?php else: ?>


        <?php foreach ($employes as $employe): ?>

            <article class="employe">


                <h2>

                    <?php
                    echo htmlspecialchars(
                        $employe['prenom']
                        . ' '
                        . $employe['nom']
                    );
                    ?>

                </h2>


                <p>

                    <strong>Email :</strong>

                    <?php
                    echo htmlspecialchars(
                        $employe['email']
                    );
                    ?>

                </p>


                <p>

                    <strong>Téléphone :</strong>

                    <?php
                    echo htmlspecialchars(
                        $employe['gsm']
                    );
                    ?>

                </p>


                <p>

                    <strong>Adresse :</strong>

                    <?php
                    echo htmlspecialchars(
                        $employe['adresse']
                    );
                    ?>

                </p>


                <p>

                    <strong>Créé le :</strong>

                    <?php
                    echo htmlspecialchars(
                        $employe['created_at']
                    );
                    ?>

                </p>


                <?php if ((int) $employe['actif'] === 1): ?>

                    <span class="actif">
                        Compte actif
                    </span>

                <?php else: ?>

                    <span class="inactif">
                        Compte désactivé
                    </span>

                <?php endif; ?>


                <form
                    method="POST"
                    action="desactiver-employe.php"
                >

                    <input
                        type="hidden"
                        name="id"
                        value="<?php
                        echo (int) $employe['id'];
                        ?>"
                    >


                    <?php if ((int) $employe['actif'] === 1): ?>

                        <input
                            type="hidden"
                            name="action"
                            value="desactiver"
                        >

                        <button
                            class="desactiver"
                            type="submit"
                        >
                            Désactiver le compte
                        </button>

                    <?php else: ?>

                        <input
                            type="hidden"
                            name="action"
                            value="activer"
                        >

                        <button
                            class="activer"
                            type="submit"
                        >
                            Réactiver le compte
                        </button>

                    <?php endif; ?>


                </form>


            </article>

        <?php endforeach; ?>


    <?php endif; ?>

</main>


</body>

</html>