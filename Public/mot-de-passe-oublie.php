<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/mail.php';

$message = '';
$erreur = '';

// --------------------------------------------------
// TRAITEMENT
// --------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['reset_requested_at'] ?? 0) > time() - 60) {
    $message = 'Si un compte correspond à cette adresse, un email de réinitialisation a été envoyé.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['reset_requested_at'] = time();

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
            'Veuillez saisir une adresse email valide.';
    } else {
        /*
         * On cherche l'utilisateur.
         *
         * On ne dira jamais à l'écran
         * si l'adresse existe ou non.
         */
        $sqlUser = '
            SELECT
                id,
                nom,
                prenom,
                email,
                actif
            FROM users

            WHERE email = :email

            LIMIT 1
        ';

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
                $sqlAncienToken = '
                    UPDATE password_reset_tokens

                    SET used = 1

                    WHERE user_id = :user_id
                    AND used = 0
                ';

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

                $sqlToken = '
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
                ';

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
                $baseUrl = applicationUrl();

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
                    'Erreur reset password : '
                    . $e->getMessage()
                );
            }
        }

        /*
         * Message volontairement identique,
         * que l'adresse existe ou non.
         */
        $message =
            'Si un compte correspond à cette adresse, '
            . 'un email de réinitialisation a été envoyé.';
    }
}

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Mot de passe oublié'); ?>
<section class="content-panel">
<div class="container">
    <h1>
        Mot de passe oublié
    </h1>
    <p>
        Saisissez l'adresse email associée
        à votre compte.
    </p>
    <?php if ($message !== ''): ?>
        <p class="message" role="status">
            <?= htmlspecialchars($message) ?>
        </p>
    <?php endif; ?>
    <?php if ($erreur !== ''): ?>
        <p class="erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>
    <form method="POST">
        <?= csrfInput() ?>
        <label for="email">Adresse email <span class="required-label">(obligatoire)</span></label>
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
</section>
<?php renderFooter($pdo); ?>
