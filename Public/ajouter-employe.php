<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/mail.php';

// Vérifier la connexion
requireLogin();

// Seul l'administrateur peut créer un employé
requireRole('admin');

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

    $erreur = identityValidationError(compact('nom', 'prenom', 'email', 'gsm', 'adresse')) ?? passwordValidationError($password) ?? '';

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

            envoyerEmail($email, $prenom . ' ' . $nom, 'Votre compte employé Vite & Gourmand', '<p>Bonjour ' . e($prenom) . ',</p><p>Votre compte employé a été créé. Contactez votre administrateur pour obtenir votre mot de passe. Aucun mot de passe n’est envoyé par email.</p>');
            header(
                'Location: admin-employes.php'
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
                    'Cette adresse email est déjà utilisée.';
            } else {
                error_log(
                    'Erreur création employé : '
                    . $e->getMessage()
                );

                $erreur =
                    'Une erreur est survenue lors de la création du compte.';
            }
        }
    }
}

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Créer un employé'); ?>
<section class="content-panel">
    <h1>
        Créer un compte employé
    </h1>
    <p>
        Le rôle attribué sera automatiquement
        <strong>employé</strong>.
    </p>
    <?php if ($erreur !== ''): ?>
        <p class="erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>
    <form method="POST">
        <?= csrfInput() ?>
        <label for="nom">Nom <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="nom"
            maxlength="50"
            autocomplete="family-name"
            name="nom"
            value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>"
            required
        >
        <label for="prenom">Prénom <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="prenom"
            maxlength="50"
            autocomplete="given-name"
            name="prenom"
            value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>"
            required
        >
        <label for="email">Email <span class="required-label">(obligatoire)</span></label>
        <input
            type="email"
            id="email"
            maxlength="100"
            autocomplete="email"
            name="email"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            required
        >
        <label for="gsm">Téléphone <span class="required-label">(obligatoire)</span></label>
        <input
            type="tel"
            id="gsm"
            maxlength="20"
            autocomplete="tel"
            name="gsm"
            value="<?= htmlspecialchars($_POST['gsm'] ?? '') ?>"
            required
        >
        <label for="adresse">Adresse <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="adresse"
            maxlength="255"
            autocomplete="street-address"
            name="adresse"
            value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>"
            required
        >
        <label for="password">Mot de passe <span class="required-label">(obligatoire)</span></label>
        <input
            type="password"
            id="password"
            autocomplete="new-password"
            minlength="10"
            maxlength="72"
            aria-describedby="password-help"
            name="password"
            required
        >
        <p id="password-help">
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
</section>
<?php renderFooter($pdo); ?>
