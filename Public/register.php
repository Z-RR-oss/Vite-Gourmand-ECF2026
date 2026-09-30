<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/mail.php';
$erreur = $succes = '';
$values = array_fill_keys(['nom', 'prenom', 'email', 'gsm', 'adresse'], '');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $field => $_) {
        $values[$field] = trim($_POST[$field] ?? '');
    }
    $password = $_POST['password'] ?? '';
    $erreur = identityValidationError($values) ?? passwordValidationError($password) ?? '';
    if ($erreur === '') {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (nom, prenom, email, gsm, adresse, password, role, actif) VALUES (?, ?, ?, ?, ?, ?, 'utilisateur', 1)");
            $stmt->execute([$values['nom'], $values['prenom'], $values['email'], $values['gsm'], $values['adresse'], password_hash($password, PASSWORD_DEFAULT)]);
            $sent = envoyerEmail($values['email'], $values['prenom'] . ' ' . $values['nom'], 'Bienvenue chez Vite & Gourmand', '<h2>Bienvenue chez Vite &amp; Gourmand</h2><p>Bonjour ' . e($values['prenom']) . ', votre compte a été créé. Vous pouvez maintenant vous connecter et commander un menu.</p>');
            $succes = 'Votre compte est créé. Vous pouvez maintenant vous connecter.';
            if (!$sent) {
                $succes .= ' L’email de bienvenue n’a pas pu être envoyé.';
            }
        } catch (PDOException $error) {
            if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
                $erreur = 'Cette adresse email est déjà utilisée.';
            } else {
                throw $error;
            }
        }
    }
}
require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Créer mon compte');
?>
<section class="content-panel auth-panel">
<p class="eyebrow">Votre prochaine occasion</p>
<h1>Créer mon compte</h1>
<p>Retrouvez vos commandes et préparez votre événement avec notre équipe.</p>
<?php if ($erreur): ?><p class="erreur" role="alert"><?= e($erreur) ?></p><?php endif; ?>
<?php if ($succes): ?>
<p class="succes" role="status"><?= e($succes) ?></p><a class="button" href="login.php">Se connecter</a>
<?php else: ?>
<form action="register.php" method="post">
<?= csrfInput() ?>
<?php foreach (['nom' => 'Nom', 'prenom' => 'Prénom', 'email' => 'Adresse email', 'gsm' => 'Téléphone', 'adresse' => 'Adresse postale'] as $field => $label): ?>
<label for="<?= $field ?>"><?= $label ?></label>
<input id="<?= $field ?>" name="<?= $field ?>" type="<?= $field === 'email' ? 'email' : ($field === 'gsm' ? 'tel' : 'text') ?>" autocomplete="<?= ['nom' => 'family-name', 'prenom' => 'given-name', 'email' => 'email', 'gsm' => 'tel', 'adresse' => 'street-address'][$field] ?>" value="<?= e($values[$field]) ?>" maxlength="<?= ['nom' => 50, 'prenom' => 50, 'email' => 100, 'gsm' => 20, 'adresse' => 255][$field] ?>" required>
<?php endforeach; ?>
<label for="password">Mot de passe</label>
<input id="password" type="password" name="password" minlength="10" maxlength="72" autocomplete="new-password" aria-describedby="password-help" required>
<p id="password-help" class="hint">10 à 72 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial.</p>
<button type="submit">Créer mon compte</button>
</form>
<p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
<?php endif; ?>
</section>
<?php renderFooter($pdo); ?>
