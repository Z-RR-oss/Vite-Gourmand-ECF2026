<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Templates/layout.php';

$erreur = '';
$maxLoginAttempts = 8;
$loginWindowSeconds = 15 * 60;
// Limitation par session uniquement ; elle ne remplace pas un contrôle global côté hébergeur.
$loginAttempts = array_filter(
    $_SESSION['login_attempts'] ?? [],
    static fn ($at) => $at > time() - $loginWindowSeconds
);

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
    header('Location: ' . authenticatedHomePath($_SESSION['role']), true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (count($loginAttempts) >= $maxLoginAttempts) {
        $erreur = 'Trop de tentatives. Réessayez dans 15 minutes.';
    } elseif ($email === '' || $password === '') {
        $erreur = 'Veuillez renseigner votre email et votre mot de passe.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = "L'adresse email n'est pas valide.";
    } else {
        $stmt = $pdo->prepare('SELECT id, nom, prenom, email, password, role, actif FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['login_attempts'] = [...$loginAttempts, time()];

        // Même message pour un compte absent et un mot de passe incorrect.
        if (!$user || !password_verify($password, $user['password'])) {
            $erreur = 'Email ou mot de passe incorrect.';
        } elseif ((int) $user['actif'] !== 1) {
            $erreur = 'Ce compte a été désactivé. Contactez un administrateur.';
        } else {
            // Renouveler l'identifiant et le CSRF à la frontière d'authentification.
            session_regenerate_id(true);
            unset($_SESSION['login_attempts']);
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            // Une modification du hash en base révoquera cette session à la requête suivante.
            $_SESSION['auth_version'] = hash('sha256', $user['password']);
            $_SESSION['user_id'] = (int) $user['id'];
            foreach (['email', 'role', 'prenom', 'nom'] as $field) {
                $_SESSION[$field] = $user[$field];
            }

            $returnTo = $_SESSION['return_to'] ?? '';
            unset($_SESSION['return_to']);
            // Liste blanche stricte : aucune redirection vers une URL externe arbitraire.
            $destination = preg_match('/^commander\.php\?id=[1-9][0-9]*$/D', $returnTo)
                ? $returnTo
                : authenticatedHomePath($user['role']);
            header('Location: ' . $destination, true, 303);
            exit;
        }
    }
}

renderHeader('Connexion');
?>
<section class="content-panel">
    <div class="container">
        <h1>Connexion</h1>
        <?php if ($erreur !== ''): ?>
            <p class="erreur" role="alert"><?= e($erreur) ?></p>
        <?php endif; ?>
        <form method="post">
            <?= csrfInput() ?>
            <label for="email">Adresse email</label>
            <input type="email" id="email" name="email"
                value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email" required>
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <p class="oubli"><a href="mot-de-passe-oublie.php">Mot de passe oublié ?</a></p>
            <button type="submit">Connexion</button>
        </form>
        <p class="retour">Première visite ? <a href="register.php">Créer mon compte</a></p>
        <a class="retour" href="index.php">← Retour à l'accueil</a>
    </div>
</section>
<?php renderFooter($pdo); ?>
