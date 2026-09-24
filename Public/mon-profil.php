<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];


// 2. Récupérer les informations actuelles
$sql = "
    SELECT
        id,
        nom,
        prenom,
        email,
        gsm,
        adresse,
        role,
        actif
    FROM users

    WHERE id = :id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $userId
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {
    exit("Utilisateur introuvable.");
}


// Compte désactivé
if ((int) $user['actif'] !== 1) {

    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}


$erreur = '';
$succes = '';


// 3. Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nom = trim(
        $_POST['nom'] ?? ''
    );

    $prenom = trim(
        $_POST['prenom'] ?? ''
    );

    $email = trim(
        $_POST['email'] ?? ''
    );

    $gsm = trim(
        $_POST['gsm'] ?? ''
    );

    $adresse = trim(
        $_POST['adresse'] ?? ''
    );


    // 4. Validation
    if (
        $nom === ''
        || $prenom === ''
        || $email === ''
        || $gsm === ''
        || $adresse === ''
    ) {

        $erreur =
            "Tous les champs sont obligatoires.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $erreur =
            "L'adresse email n'est pas valide.";
    }


    // 5. Vérifier que l'email
    // n'est pas utilisé par un autre compte
    if ($erreur === '') {

        $sqlEmail = "
            SELECT id
            FROM users

            WHERE email = :email
            AND id != :id
        ";

        $stmtEmail = $pdo->prepare(
            $sqlEmail
        );

        $stmtEmail->execute([
            ':email' => $email,
            ':id' => $userId
        ]);


        if ($stmtEmail->fetch()) {

            $erreur =
                "Cette adresse email est déjà utilisée.";
        }
    }


    // 6. Modifier les informations
    if ($erreur === '') {

        try {

            $sqlUpdate = "
                UPDATE users

                SET
                    nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    gsm = :gsm,
                    adresse = :adresse

                WHERE id = :id
            ";

            $stmtUpdate = $pdo->prepare(
                $sqlUpdate
            );

            $stmtUpdate->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':gsm' => $gsm,
                ':adresse' => $adresse,
                ':id' => $userId
            ]);


            // Mettre également la session à jour
            $_SESSION['email'] = $email;
            $_SESSION['nom'] = $nom;
            $_SESSION['prenom'] = $prenom;


            // Mettre les valeurs affichées à jour
            $user['nom'] = $nom;
            $user['prenom'] = $prenom;
            $user['email'] = $email;
            $user['gsm'] = $gsm;
            $user['adresse'] = $adresse;


            $succes =
                "Vos informations ont bien été mises à jour.";


        } catch (PDOException $e) {

            $errorInfo =
                $e->errorInfo;


            if (
                isset($errorInfo[1])
                && (int) $errorInfo[1] === 1062
            ) {

                $erreur =
                    "Cette adresse email est déjà utilisée.";

            } else {

                error_log(
                    "Erreur modification profil : "
                    . $e->getMessage()
                );

                $erreur =
                    "Une erreur est survenue lors de la modification du profil.";
            }
        }
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
        Mon profil - Vite & Gourmand
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;

            background-color: #f4f4f4;

            margin: 0;

            padding: 20px;
        }


        .navbar {
            max-width: 900px;

            margin: 0 auto 30px auto;

            padding: 15px 20px;

            background: #111;
            color: white;

            border-radius: 10px;

            display: flex;

            justify-content: space-between;
            align-items: center;
        }


        .navbar a {
            color: white;

            text-decoration: none;

            margin-left: 15px;
        }


        .navbar a:hover {
            color: orange;
        }


        main {
            max-width: 650px;

            margin: auto;

            padding: 30px;

            background: white;

            border-radius: 12px;

            box-shadow:
                0 2px 10px rgba(0, 0, 0, 0.1);
        }


        h1 {
            margin-top: 0;
        }


        label {
            display: block;

            margin-top: 15px;
            margin-bottom: 6px;

            font-weight: bold;
        }


        input {
            width: 100%;

            padding: 11px;

            box-sizing: border-box;

            border: 1px solid #ccc;

            border-radius: 6px;

            font-size: 16px;
        }


        button {
            width: 100%;

            margin-top: 25px;

            padding: 13px;

            border: none;

            border-radius: 6px;

            background: black;
            color: white;

            font-size: 16px;
            font-weight: bold;

            cursor: pointer;
        }


        button:hover {
            background: #333;
        }


        .erreur {
            padding: 12px;

            margin-bottom: 20px;

            background: #ffdede;
            color: #8b0000;

            border-radius: 6px;
        }


        .succes {
            padding: 12px;

            margin-bottom: 20px;

            background: #e1f5e1;
            color: #176b3a;

            border-radius: 6px;
        }


        .role {
            padding: 12px;

            margin-bottom: 20px;

            background: #f5f5f5;

            border-radius: 6px;
        }


        .logout {
            color: #ffb3b3 !important;
        }


        @media (max-width: 700px) {

            .navbar {
                flex-direction: column;

                gap: 12px;

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

        <a href="index.php">
            Accueil
        </a>

        <a href="mes-commandes.php">
            Mes commandes
        </a>

        <a href="mon-profil.php">
            Mon profil
        </a>

        <a
            class="logout"
            href="logout.php"
        >
            Déconnexion
        </a>

    </div>

</nav>


<main>


    <h1>
        Mon profil
    </h1>


    <div class="role">

        Connecté en tant que :

        <strong>
            <?php
            echo htmlspecialchars(
                $user['prenom']
                . ' '
                . $user['nom']
            );
            ?>
        </strong>

    </div>


    <?php if ($erreur !== ''): ?>

        <p class="erreur">

            <?php
            echo htmlspecialchars(
                $erreur
            );
            ?>

        </p>

    <?php endif; ?>


    <?php if ($succes !== ''): ?>

        <p class="succes">

            <?php
            echo htmlspecialchars(
                $succes
            );
            ?>

        </p>

    <?php endif; ?>


    <form method="POST">


        <label for="nom">
            Nom
        </label>

        <input
            type="text"
            id="nom"
            name="nom"
            value="<?php
            echo htmlspecialchars(
                $user['nom']
            );
            ?>"
            required
        >


        <label for="prenom">
            Prénom
        </label>

        <input
            type="text"
            id="prenom"
            name="prenom"
            value="<?php
            echo htmlspecialchars(
                $user['prenom']
            );
            ?>"
            required
        >


        <label for="email">
            Adresse email
        </label>

        <input
            type="email"
            id="email"
            name="email"
            value="<?php
            echo htmlspecialchars(
                $user['email']
            );
            ?>"
            required
        >


        <label for="gsm">
            Téléphone
        </label>

        <input
            type="text"
            id="gsm"
            name="gsm"
            value="<?php
            echo htmlspecialchars(
                $user['gsm']
            );
            ?>"
            required
        >


        <label for="adresse">
            Adresse postale
        </label>

        <input
            type="text"
            id="adresse"
            name="adresse"
            value="<?php
            echo htmlspecialchars(
                $user['adresse']
            );
            ?>"
            required
        >


        <button type="submit">
            Enregistrer mes modifications
        </button>


    </form>


</main>


</body>

</html>