<?php

require_once __DIR__ . '/../Config/database.php';

$erreur = '';
$loginAttempts = $_SESSION['login_attempts'] ?? [];
$loginAttempts = array_filter($loginAttempts, static fn ($at) => $at > time() - 900);

// Si l'utilisateur est déjà connecté,
// on peut le rediriger directement.
if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    if (
        $_SESSION['role'] === 'admin'
        || $_SESSION['role'] === 'employe'
    ) {
        header('Location: admin-commandes.php');
        exit;
    }

    header('Location: mes-commandes.php');
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
    if (count($loginAttempts) >= 8) {
        $erreur = 'Trop de tentatives. Réessayez dans 15 minutes.';
    } elseif ($email === '' || $password === '') {
        $erreur =
            'Veuillez renseigner votre email et votre mot de passe.';
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
        $sql = '
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
        ';

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':email' => $email
        ]);

        $user = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        $_SESSION['login_attempts'] = [...$loginAttempts, time()];
        // Vérifier email + mot de passe
        if (
            !$user
            || !password_verify(
                $password,
                $user['password']
            )
        ) {
            $erreur =
                'Email ou mot de passe incorrect.';
        } elseif ((int) $user['actif'] !== 1) {
            // Le mot de passe est correct,
            // mais le compte a été désactivé.
            $erreur =
                'Ce compte a été désactivé. Contactez un administrateur.';
        } else {
            /*
             * La connexion est valide.
             *
             * On régénère l'identifiant de session
             * pour éviter de conserver l'ancien ID.
             */
            session_regenerate_id(true);
            unset($_SESSION['login_attempts']);
            $_SESSION['auth_version'] = hash('sha256', $user['password']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

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

            $returnTo = $_SESSION['return_to'] ?? '';
            unset($_SESSION['return_to']);
            if (preg_match('/^commander\.php\?id=[1-9][0-9]*$/D', $returnTo)) {
                header('Location: ' . $returnTo, true, 303);
                exit;
            }

            // Redirection selon le rôle
            if (
                $user['role'] === 'admin'
                || $user['role'] === 'employe'
            ) {
                header(
                    'Location: admin-commandes.php'
                );

                exit;
            }

            header(
                'Location: mes-commandes.php'
            );

            exit;
        }
    }
}

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Connexion'); ?>
<section class="content-panel">
    <div class="container">
        <h1>
            Connexion
        </h1>
        <?php if ($erreur !== ''): ?>
            <p class="erreur">
                <?= htmlspecialchars($erreur) ?>
            </p>
        <?php endif; ?>
        <form method="POST">
        <?= csrfInput() ?>
            <label for="email">
                Adresse email
            </label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
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
    <a href="mot-de-passe-oublie.php">
        Mot de passe oublié ?
    </a>
</p>
            <button type="submit">
                Connexion
            </button>
        </form>
        <p class="retour">Première visite ? <a href="register.php">Créer mon compte</a></p>
        <a
            class="retour"
            href="index.php"
        >
            ← Retour à l'accueil
        </a>
    </div>
</section>
<?php renderFooter($pdo); ?>
