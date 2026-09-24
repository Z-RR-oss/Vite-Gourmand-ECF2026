<?php

session_start();
require_once '../Config/database.php';

$erreur = '';


// Si l'utilisateur est déjà connecté,
// on peut le rediriger directement.
if (isset($_SESSION['user_id'], $_SESSION['role'])) {

    if (
        $_SESSION['role'] === 'admin'
        || $_SESSION['role'] === 'employe'
    ) {

        header("Location: admin-commandes.php");
        exit;
    }


    header("Location: mes-commandes.php");
    exit;
}


// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim(
        $_POST['email'] ?? ''
    );

    $password =
        $_POST['password'] ?? '';


    // Vérification des champs
    if ($email === '' || $password === '') {

        $erreur =
            "Veuillez renseigner votre email et votre mot de passe.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $erreur =
            "L'adresse email n'est pas valide.";

    } else {


        // Chercher l'utilisateur
        $sql = "
            SELECT
                id,
                nom,
                prenom,
                email,
                password,
                role,
                actif
            FROM users

            WHERE email = :email

            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);


        $user = $stmt->fetch(
            PDO::FETCH_ASSOC
        );


        // Vérifier email + mot de passe
        if (
            !$user
            || !password_verify(
                $password,
                $user['password']
            )
        ) {

            $erreur =
                "Email ou mot de passe incorrect.";

        } elseif ((int) $user['actif'] !== 1) {

            // Le mot de passe est correct,
            // mais le compte a été désactivé.
            $erreur =
                "Ce compte a été désactivé. Contactez un administrateur.";

        } else {


            /*
             * La connexion est valide.
             *
             * On régénère l'identifiant de session
             * pour éviter de conserver l'ancien ID.
             */
            session_regenerate_id(true);


            $_SESSION['user_id'] =
                (int) $user['id'];

            $_SESSION['email'] =
                $user['email'];

            $_SESSION['role'] =
                $user['role'];

            $_SESSION['prenom'] =
                $user['prenom'];

            $_SESSION['nom'] =
                $user['nom'];


            // Redirection selon le rôle
            if (
                $user['role'] === 'admin'
                || $user['role'] === 'employe'
            ) {

                header(
                    "Location: admin-commandes.php"
                );

                exit;
            }


            header(
                "Location: mes-commandes.php"
            );

            exit;
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
        Connexion - Vite & Gourmand
    </title>


    <style>

        body {
            font-family: Arial, sans-serif;

            background-color: #f4f4f4;

            margin: 0;

            padding: 20px;
        }


        main {
            min-height: 90vh;

            display: flex;

            justify-content: center;
            align-items: center;
        }


        .container {
            width: 100%;
            max-width: 450px;

            background: white;

            padding: 30px;

            border-radius: 12px;

            box-shadow:
                0 2px 10px rgba(0, 0, 0, 0.1);
        }


        h1 {
            margin-top: 0;

            text-align: center;
        }


        label {
            display: block;

            margin-top: 15px;
            margin-bottom: 6px;

            font-weight: bold;
        }


        input {
            width: 100%;

            padding: 12px;

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
            background: #ffdede;

            color: #8b0000;

            padding: 12px;

            margin-bottom: 20px;

            border-radius: 6px;
        }


        .oubli {
            margin-top: 15px;

            text-align: center;

            color: #555;
        }


        .retour {
            display: block;

            margin-top: 20px;

            text-align: center;
        }


    </style>

</head>


<body>


<main>


    <div class="container">


        <h1>
            Connexion
        </h1>


        <?php if ($erreur !== ''): ?>

            <p class="erreur">

                <?php
                echo htmlspecialchars(
                    $erreur
                );
                ?>

            </p>

        <?php endif; ?>


        <form method="POST">


            <label for="email">
                Adresse email
            </label>


            <input
                type="email"
                id="email"
                name="email"
                value="<?php
                echo htmlspecialchars(
                    $_POST['email'] ?? ''
                );
                ?>"
                autocomplete="email"
                required
            >


            <label for="password">
                Mot de passe
            </label>


            <input
                type="password"
                id="password"
                name="password"
                autocomplete="current-password"
                required
            >


            <p class="oubli">
                Mot de passe oublié ?
            </p>


            <button type="submit">
                Connexion
            </button>


        </form>


        <a
            class="retour"
            href="index.php"
        >
            ← Retour à l'accueil
        </a>


    </div>


</main>


</body>

</html>