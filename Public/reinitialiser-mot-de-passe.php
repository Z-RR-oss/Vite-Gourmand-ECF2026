<?php

require_once '../Config/database.php';

$token =
    $_GET['token']
    ?? $_POST['token']
    ?? '';

$erreur = '';

$succes = false;

$tokenValide = false;

$reset = null;


// --------------------------------------------------
// VÉRIFIER LE TOKEN
// --------------------------------------------------

if ($token !== '') {

    $tokenHash =
        hash(
            'sha256',
            $token
        );


    $sqlToken = "
        SELECT
            password_reset_tokens.id,
            password_reset_tokens.user_id,
            password_reset_tokens.expires_at,
            password_reset_tokens.used,
            users.email,
            users.actif

        FROM password_reset_tokens

        INNER JOIN users
            ON users.id =
               password_reset_tokens.user_id

        WHERE password_reset_tokens.token_hash
              = :token_hash

        AND password_reset_tokens.used = 0

        AND password_reset_tokens.expires_at
            > NOW()

        AND users.actif = 1

        LIMIT 1
    ";


    $stmtToken =
        $pdo->prepare(
            $sqlToken
        );


    $stmtToken->execute([
        ':token_hash' =>
            $tokenHash
    ]);


    $reset =
        $stmtToken->fetch(
            PDO::FETCH_ASSOC
        );


    if ($reset) {

        $tokenValide = true;
    }
}


// --------------------------------------------------
// TRAITER LE NOUVEAU MOT DE PASSE
// --------------------------------------------------

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $tokenValide
) {

    $password =
        $_POST['password']
        ?? '';

    $confirmation =
        $_POST['confirmation']
        ?? '';


    // Minimum 10 caractères
    if (strlen($password) < 10) {

        $erreur =
            "Le mot de passe doit contenir "
            . "au moins 10 caractères.";

    } elseif (
        !preg_match(
            '/[A-Z]/',
            $password
        )
    ) {

        $erreur =
            "Le mot de passe doit contenir "
            . "au moins une majuscule.";

    } elseif (
        !preg_match(
            '/[a-z]/',
            $password
        )
    ) {

        $erreur =
            "Le mot de passe doit contenir "
            . "au moins une minuscule.";

    } elseif (
        !preg_match(
            '/[0-9]/',
            $password
        )
    ) {

        $erreur =
            "Le mot de passe doit contenir "
            . "au moins un chiffre.";

    } elseif (
        !preg_match(
            '/[^a-zA-Z0-9]/',
            $password
        )
    ) {

        $erreur =
            "Le mot de passe doit contenir "
            . "au moins un caractère spécial.";

    } elseif (
        $password !==
        $confirmation
    ) {

        $erreur =
            "Les deux mots de passe "
            . "ne correspondent pas.";
    }


    if ($erreur === '') {


        try {

            $pdo->beginTransaction();


            /*
             * Relire et verrouiller le token
             * afin qu'il ne puisse pas être
             * utilisé deux fois en parallèle.
             */
            $sqlVerification = "
                SELECT
                    id,
                    user_id

                FROM password_reset_tokens

                WHERE token_hash = :token_hash

                AND used = 0

                AND expires_at > NOW()

                LIMIT 1

                FOR UPDATE
            ";


            $stmtVerification =
                $pdo->prepare(
                    $sqlVerification
                );


            $stmtVerification->execute([
                ':token_hash' =>
                    $tokenHash
            ]);


            $tokenBDD =
                $stmtVerification->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$tokenBDD) {

                throw new Exception(
                    "Ce lien n'est plus valide."
                );
            }


            $nouveauHash =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            // Modifier le mot de passe
            $sqlPassword = "
                UPDATE users

                SET password = :password

                WHERE id = :user_id
            ";


            $stmtPassword =
                $pdo->prepare(
                    $sqlPassword
                );


            $stmtPassword->execute([

                ':password' =>
                    $nouveauHash,

                ':user_id' =>
                    $tokenBDD['user_id']
            ]);


            /*
             * Désactiver tous les liens
             * de réinitialisation de ce compte.
             */
            $sqlUsed = "
                UPDATE password_reset_tokens

                SET used = 1

                WHERE user_id = :user_id
                AND used = 0
            ";


            $stmtUsed =
                $pdo->prepare(
                    $sqlUsed
                );


            $stmtUsed->execute([
                ':user_id' =>
                    $tokenBDD['user_id']
            ]);


            $pdo->commit();


            $succes = true;

            $tokenValide = false;


        } catch (Throwable $e) {


            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }


            error_log(
                "Erreur changement mot de passe : "
                . $e->getMessage()
            );


            $erreur =
                "Une erreur est survenue. "
                . "Veuillez recommencer.";
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
        Nouveau mot de passe - Vite & Gourmand
    </title>


    <style>

        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
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
                0 2px 10px
                rgba(0, 0, 0, 0.1);
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
        }

        button {
            width: 100%;
            margin-top: 20px;
            padding: 13px;
            border: none;
            border-radius: 6px;
            background: black;
            color: white;
            cursor: pointer;
        }

        .erreur {
            background: #ffdede;
            color: #8b0000;
            padding: 12px;
            border-radius: 6px;
        }

        .succes {
            background: #ddffdd;
            padding: 12px;
            border-radius: 6px;
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


<?php if ($succes): ?>


    <h1>
        Mot de passe modifié
    </h1>


    <p class="succes">
        Votre mot de passe a été
        modifié avec succès.
    </p>


    <a
        class="retour"
        href="login.php"
    >
        Se connecter
    </a>


<?php elseif (!$tokenValide): ?>


    <h1>
        Lien invalide
    </h1>


    <p class="erreur">
        Ce lien de réinitialisation
        est invalide ou a expiré.
    </p>


    <a
        class="retour"
        href="mot-de-passe-oublie.php"
    >
        Demander un nouveau lien
    </a>


<?php else: ?>


    <h1>
        Nouveau mot de passe
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


        <input
            type="hidden"
            name="token"
            value="<?php
            echo htmlspecialchars(
                $token
            );
            ?>"
        >


        <label for="password">
            Nouveau mot de passe
        </label>


        <input
            type="password"
            id="password"
            name="password"
            autocomplete="new-password"
            required
        >


        <label for="confirmation">
            Confirmer le mot de passe
        </label>


        <input
            type="password"
            id="confirmation"
            name="confirmation"
            autocomplete="new-password"
            required
        >


        <button type="submit">
            Modifier mon mot de passe
        </button>


    </form>


<?php endif; ?>


</div>

</main>


</body>

</html>