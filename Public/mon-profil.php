<?php

require_once __DIR__ . '/../Config/database.php';

requireLogin();

$userId = (int) $_SESSION['user_id'];

$sql = '
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
';

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $userId
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    abortRequest(404, 'Utilisateur introuvable.');
}

// Compte désactivé
if ((int) $user['actif'] !== 1) {
    session_unset();
    session_destroy();

    header('Location: login.php');
    exit;
}

$erreur = '';
$succes = '';

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

    $erreur = identityValidationError(compact('nom', 'prenom', 'email', 'gsm', 'adresse')) ?? '';

    // n'est pas utilisé par un autre compte
    if ($erreur === '') {
        $sqlEmail = '
            SELECT id
            FROM users

            WHERE email = :email
            AND id != :id
        ';

        $stmtEmail = $pdo->prepare(
            $sqlEmail
        );

        $stmtEmail->execute([
            ':email' => $email,
            ':id' => $userId
        ]);

        if ($stmtEmail->fetch()) {
            $erreur =
                'Cette adresse email est déjà utilisée.';
        }
    }

    if ($erreur === '') {
        try {
            $sqlUpdate = '
                UPDATE users

                SET
                    nom = :nom,
                    prenom = :prenom,
                    email = :email,
                    gsm = :gsm,
                    adresse = :adresse

                WHERE id = :id
            ';

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
                'Vos informations ont bien été mises à jour.';
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
                    'Erreur modification profil : '
                    . $e->getMessage()
                );

                $erreur =
                    'Une erreur est survenue lors de la modification du profil.';
            }
        }
    }
}

// Une erreur ne doit pas obliger la personne à ressaisir toutes ses coordonnées.
if ($erreur !== '') {
    foreach (['nom', 'prenom', 'email', 'gsm', 'adresse'] as $field) {
        $user[$field] = $_POST[$field] ?? '';
    }
}
?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Mon profil'); ?>
<section class="content-panel">
    <h1>
        Mon profil
    </h1>
    <div class="role">

        Connecté en tant que :

        <strong>
            <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
        </strong>
    </div>
    <?php if ($erreur !== ''): ?>
        <p class="erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>
    <?php if ($succes !== ''): ?>
        <p class="succes" role="status">
            <?= htmlspecialchars($succes) ?>
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
            value="<?= htmlspecialchars($user['nom']) ?>"
            required
        >
        <label for="prenom">Prénom <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="prenom"
            maxlength="50"
            autocomplete="given-name"
            name="prenom"
            value="<?= htmlspecialchars($user['prenom']) ?>"
            required
        >
        <label for="email">Adresse email <span class="required-label">(obligatoire)</span></label>
        <input
            type="email"
            id="email"
            maxlength="100"
            autocomplete="email"
            name="email"
            value="<?= htmlspecialchars($user['email']) ?>"
            required
        >
        <label for="gsm">Téléphone <span class="required-label">(obligatoire)</span></label>
        <input
            type="tel"
            id="gsm"
            maxlength="20"
            autocomplete="tel"
            name="gsm"
            value="<?= htmlspecialchars($user['gsm']) ?>"
            required
        >
        <label for="adresse">Adresse postale <span class="required-label">(obligatoire)</span></label>
        <input
            type="text"
            id="adresse"
            maxlength="255"
            autocomplete="street-address"
            name="adresse"
            value="<?= htmlspecialchars($user['adresse']) ?>"
            required
        >
        <button type="submit">
            Enregistrer mes modifications
        </button>
    </form>
</section>
<?php renderFooter($pdo); ?>
