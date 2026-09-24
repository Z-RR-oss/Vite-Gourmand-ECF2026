<?php

session_start();
require_once '../Config/database.php';


// Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// Seul l'administrateur peut créer un employé
if ($_SESSION['role'] !== 'admin') {
    exit("Accès refusé.");
}


$erreur = '';


// Traitement du formulaire
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

    $password =
        $_POST['password'] ?? '';


    // Validation des champs
    if (
        $nom === ''
        || $prenom === ''
        || $email === ''
        || $gsm === ''
        || $adresse === ''
        || $password === ''
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


    // Vérification du mot de passe
    $majuscule = false;
    $minuscule = false;
    $chiffre = false;
    $special = false;


    for (
        $i = 0;
        $i < strlen($password);
        $i++
    ) {

        $caractere = $password[$i];


        if (ctype_upper($caractere)) {

            $majuscule = true;

        } elseif (ctype_lower($caractere)) {

            $minuscule = true;

        } elseif (ctype_digit($caractere)) {

            $chiffre = true;

        } else {

            $special = true;
        }
    }


    if (
        $erreur === ''
        && strlen($password) < 10
    ) {

        $erreur =
            "Le mot de passe doit contenir au moins 10 caractères.";

    } elseif (
        $erreur === ''
        && !$majuscule
    ) {

        $erreur =
            "Le mot de passe doit contenir au moins une majuscule.";

    } elseif (
        $erreur === ''
        && !$minuscule
    ) {

        $erreur =
            "Le mot de passe doit contenir au moins une minuscule.";

    } elseif (
        $erreur === ''
        && !$chiffre
    ) {

        $erreur =
            "Le mot de passe doit contenir au moins un chiffre.";

    } elseif (
        $erreur === ''
        && !$special
    ) {

        $erreur =
            "Le mot de passe doit contenir au moins un caractère spécial.";
    }


    // Création du compte
    if ($erreur === '') {

        $passwordHash =
            password_hash(
                $password,
                PASSWORD_DEFAULT
            );


        try {

            /*
             * Le rôle est volontairement écrit
             * directement dans la requête.
             *
             * L'administrateur ne peut donc pas
             * créer un autre administrateur
             * depuis cette page.
             */
            $sql = "
                INSERT INTO users (
                    nom,
                    prenom,
                    email,
                    password,
                    role,
                    gsm,
                    adresse,
                    actif
                )

                VALUES (
                    :nom,
                    :prenom,
                    :email,
                    :password,
                    'employe',
                    :gsm,
                    :adresse,
                    1
                )
            ";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                ':nom' => $nom,
                ':prenom' => $prenom,
                ':email' => $email,
                ':password' => $passwordHash,
                ':gsm' => $gsm,
                ':adresse' => $adresse
            ]);


            header(
                "Location: admin-employes.php"
            );

            exit;


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
                    "Erreur création employé : "
                    . $e->getMessage()
                );

                $erreur =
                    "Une erreur est survenue lors de la création du compte.";
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
        Créer un employé - Vite & Gourmand
    </title>

    <style>

        body {
            font-family: Arial, sans-serif;

            background: #f4f4f4;

            padding: 20px;
        }

        main {
            max-width: 650px;

            margin: auto;

            background: white;

            padding: 30px;

            border-radius: 10px;

            box-shadow:
                0 2px 8px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;

            margin-top: 15px;

            margin-bottom: 5px;

            font-weight: bold;
        }

        input {
            width: 100%;

            box-sizing: border-box;

            padding: 10px;

            border: 1px solid #ccc;

            border-radius: 5px;
        }

        button {
            width: 100%;

            margin-top: 25px;

            padding: 13px;

            border: none;

            border-radius: 6px;

            background: black;

            color: white;

            font-weight: bold;

            cursor: pointer;
        }

        .erreur {
            background: #ffdede;

            color: #8b0000;

            padding: 12px;

            border-radius: 5px;
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


    <h1>
        Créer un compte employé
    </h1>


    <p>
        Le rôle attribué sera automatiquement
        <strong>employé</strong>.
    </p>


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


        <label for="nom">
            Nom
        </label>

        <input
            type="text"
            id="nom"
            name="nom"
            value="<?php
            echo htmlspecialchars(
                $_POST['nom'] ?? ''
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
                $_POST['prenom'] ?? ''
            );
            ?>"
            required
        >


        <label for="email">
            Email
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
                $_POST['gsm'] ?? ''
            );
            ?>"
            required
        >


        <label for="adresse">
            Adresse
        </label>

        <input
            type="text"
            id="adresse"
            name="adresse"
            value="<?php
            echo htmlspecialchars(
                $_POST['adresse'] ?? ''
            );
            ?>"
            required
        >


        <label for="password">
            Mot de passe
        </label>

        <input
            type="password"
            id="password"
            name="password"
            required
        >


        <p>
            Minimum 10 caractères avec une majuscule,
            une minuscule, un chiffre et un caractère spécial.
        </p>


        <button type="submit">
            Créer le compte employé
        </button>


    </form>


    <a
        class="retour"
        href="admin-employes.php"
    >
        ← Retour aux employés
    </a>


</main>

</body>

</html>