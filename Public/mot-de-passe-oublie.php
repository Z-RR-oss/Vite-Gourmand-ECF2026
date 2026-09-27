<?php

require_once '../Config/database.php';
require_once '../Config/mail.php';

$message = '';
$erreur = '';


// --------------------------------------------------
// TRAITEMENT
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email =
        trim(
            $_POST['email']
            ?? ''
        );


    if (
        $email === ''
        || !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $erreur =
            "Veuillez saisir une adresse email valide.";

    } else {


        /*
         * On cherche l'utilisateur.
         *
         * On ne dira jamais à l'écran
         * si l'adresse existe ou non.
         */
        $sqlUser = "
            SELECT
                id,
                nom,
                prenom,
                email,
                actif
            FROM users

            WHERE email = :email

            LIMIT 1
        ";


        $stmtUser =
            $pdo->prepare(
                $sqlUser
            );


        $stmtUser->execute([
            ':email' => $email
        ]);


        $user =
            $stmtUser->fetch(
                PDO::FETCH_ASSOC
            );


        if (
            $user
            && (int) $user['actif'] === 1
        ) {


            try {

                $pdo->beginTransaction();


                /*
                 * Désactiver les anciens liens
                 * encore inutilisés.
                 */
                $sqlAncienToken = "
                    UPDATE password_reset_tokens

                    SET used = 1

                    WHERE user_id = :user_id
                    AND used = 0
                ";


                $stmtAncienToken =
                    $pdo->prepare(
                        $sqlAncienToken
                    );


                $stmtAncienToken->execute([
                    ':user_id' =>
                        $user['id']
                ]);


                /*
                 * Générer un token aléatoire.
                 *
                 * Le vrai token est envoyé
                 * à l'utilisateur.
                 *
                 * Seul son hash est enregistré
                 * dans la base.
                 */
                $token =
                    bin2hex(
                        random_bytes(32)
                    );


                $tokenHash =
                    hash(
                        'sha256',
                        $token
                    );


                $expiration =
                    date(
                        'Y-m-d H:i:s',
                        time() + 3600
                    );


                $sqlToken = "
                    INSERT INTO
                    password_reset_tokens (
                        user_id,
                        token_hash,
                        expires_at,
                        used
                    )

                    VALUES (
                        :user_id,
                        :token_hash,
                        :expires_at,
                        0
                    )
                ";


                $stmtToken =
                    $pdo->prepare(
                        $sqlToken
                    );


                $stmtToken->execute([

                    ':user_id' =>
                        $user['id'],

                    ':token_hash' =>
                        $tokenHash,

                    ':expires_at' =>
                        $expiration
                ]);


                $pdo->commit();


                // URL de réinitialisation
                $baseUrl =
                    defined('APP_URL')
                    ? APP_URL
                    : 'http://vite-gourmand.local';


                $lien =
                    rtrim(
                        $baseUrl,
                        '/'
                    )
                    . '/reinitialiser-mot-de-passe.php?token='
                    . urlencode($token);


                $prenom =
                    htmlspecialchars(
                        $user['prenom']
                    );


                $contenuEmail = "
                    <h2>Réinitialisation de votre mot de passe</h2>

                    <p>
                        Bonjour {$prenom},
                    </p>

                    <p>
                        Vous avez demandé à réinitialiser
                        votre mot de passe Vite & Gourmand.
                    </p>

                    <p>
                        Cliquez sur le lien suivant :
                    </p>

                    <p>
                        <a href=\""
                        . htmlspecialchars(
                            $lien,
                            ENT_QUOTES
                        )
                        . "\">
                            Réinitialiser mon mot de passe
                        </a>
                    </p>

                    <p>
                        Ce lien est valable pendant 1 heure.
                    </p>

                    <p>
                        Si vous n'êtes pas à l'origine
                        de cette demande,
                        vous pouvez ignorer cet email.
                    </p>
                ";


                envoyerEmail(
                    $user['email'],
                    $user['prenom']
                        . ' '
                        . $user['nom'],
                    'Réinitialisation de votre mot de passe',
                    $contenuEmail
                );


            } catch (Throwable $e) {


                if (
                    $pdo->inTransaction()
                ) {

                    $pdo->rollBack();
                }


                error_log(
                    "Erreur reset password : "
                    . $e->getMessage()
                );
            }
        }


        /*
         * Message volontairement identique,
         * que l'adresse existe ou non.
         */
        $message =
            "Si un compte correspond à cette adresse, "
            . "un email de réinitialisation a été envoyé.";
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
        Mot de passe oublié - Vite & Gourmand
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

        .message {
            background: #ddffdd;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .erreur {
            background: #ffdede;
            color: #8b0000;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
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
        Mot de passe oublié
    </h1>


    <p>
        Saisissez l'adresse email associée
        à votre compte.
    </p>


    <?php if ($message !== ''): ?>

        <p class="message">

            <?php
            echo htmlspecialchars(
                $message
            );
            ?>

        </p>

    <?php endif; ?>


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
            required
            autocomplete="email"
        >


        <button type="submit">
            Envoyer le lien
        </button>


    </form>


    <a
        class="retour"
        href="login.php"
    >
        ← Retour à la connexion
    </a>


</div>

</main>


</body>

</html>