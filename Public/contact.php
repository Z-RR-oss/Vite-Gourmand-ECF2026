<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/mail.php';
require_once __DIR__ . '/../Templates/layout.php';
$values = ['titre' => '', 'description' => '', 'email' => ''];
$errors = [];
$sent = !empty($_SESSION['contact_sent']);
unset($_SESSION['contact_sent']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $name => $unused) {
        $values[$name] = is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : '';
    }
    if (mb_strlen($values['titre']) < 3 || mb_strlen($values['titre']) > 120 || preg_match('/[\r\n]/', $values['titre'])) {
        $errors['titre'] = 'Indiquez un titre de 3 à 120 caractères, sur une seule ligne.';
    }
    if (mb_strlen($values['description']) < 10 || mb_strlen($values['description']) > 5000) {
        $errors['description'] = 'Votre message doit contenir entre 10 et 5 000 caractères.';
    }
    if (strlen($values['email']) > 254 || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Indiquez une adresse email valide pour recevoir notre réponse.';
    }
    if (time() - (int) ($_SESSION['last_contact_sent'] ?? 0) < 60) {
        $errors['general'] = 'Votre précédent message a bien été envoyé. Patientez une minute avant un nouvel envoi.';
    }
    if (!$errors) {
        try {
            $recipient = (string) mailSetting('CONTACT_EMAIL', mailSetting('SMTP_FROM_EMAIL', ''));
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Destinataire contact non configuré.');
            }
            $content = '<h1>Nouvelle demande de contact</h1><p><strong>Objet :</strong> ' . e($values['titre']) . '</p><p><strong>Email :</strong> ' . e($values['email']) . '</p><p>' . nl2br(e($values['description'])) . '</p>';
            if (!envoyerEmail($recipient, 'Vite & Gourmand', 'Contact — ' . $values['titre'], $content, $values['email'])) {
                throw new RuntimeException('Envoi du contact refusé par le service email.');
            }
            $_SESSION['last_contact_sent'] = time();
            $_SESSION['contact_sent'] = true;
            header('Location: contact.php', true, 303);
            exit;
        } catch (Throwable $exception) {
            error_log('Contact : ' . $exception->getMessage());
            $errors['general'] = 'Votre message n’a pas pu être envoyé. Réessayez dans quelques instants.';
        }
    }
}
renderHeader('Parlons de votre événement');
?>
<div class="contact-layout">
    <section class="contact-intro"><p class="eyebrow">FAISONS CONNAISSANCE</p><h1>Une belle table<br>commence par<br> <em>un échange.</em></h1><p>Un repas de famille, une fête ou une question sur nos menus ? Julie et José sont à votre écoute.</p><div class="contact-detail"><span>BORDEAUX & SES ALENTOURS</span><p>Précisez la date de votre événement et le nombre de convives pour nous aider à vous répondre.</p></div><img src="assets/images/embleme.svg" alt="" width="76" height="76"></section>
    <section class="contact-form-panel" aria-labelledby="contact-form-title"><h2 id="contact-form-title">Écrivez-nous.</h2><p class="small-note">Tous les champs sont obligatoires.</p>
        <?php if ($sent): ?><div class="alert alert-success" role="status"><strong>Votre message a bien été envoyé.</strong><br>Notre équipe pourra vous répondre à l’adresse indiquée.</div><?php endif; ?>
        <?php if ($errors): ?><div class="alert alert-error" role="alert"><strong><?= isset($errors['general']) ? e($errors['general']) : 'Vérifiez les champs indiqués ci-dessous.' ?></strong></div><?php endif; ?>
        <form method="post" action="contact.php" class="stacked-form">
            <?= csrfInput() ?>
            <div class="form-group"><label for="titre">L’objet de votre message <span class="required-label">(obligatoire)</span></label><input id="titre" name="titre" required minlength="3" maxlength="120" value="<?= e($values['titre']) ?>" placeholder="Par exemple : un déjeuner en famille" <?= isset($errors['titre']) ? 'aria-invalid="true" aria-describedby="titre-error"' : '' ?>><?php if (isset($errors['titre'])): ?><p id="titre-error" class="field-error"><?= e($errors['titre']) ?></p><?php endif; ?></div>
            <div class="form-group"><label for="email">Votre adresse email <span class="required-label">(obligatoire)</span></label><input id="email" name="email" type="email" autocomplete="email" required maxlength="254" value="<?= e($values['email']) ?>" placeholder="vous@exemple.fr" <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-error"' : '' ?>><?php if (isset($errors['email'])): ?><p id="email-error" class="field-error"><?= e($errors['email']) ?></p><?php endif; ?></div>
            <div class="form-group"><label for="description">Racontez-nous votre projet <span class="required-label">(obligatoire)</span></label><textarea id="description" name="description" rows="7" required minlength="10" maxlength="5000" placeholder="Vos envies, la date, le nombre de convives…" aria-describedby="message-help<?= isset($errors['description']) ? ' description-error' : '' ?>" <?= isset($errors['description']) ? 'aria-invalid="true"' : '' ?>><?= e($values['description']) ?></textarea><p id="message-help" class="small-note">De 10 à 5 000 caractères. Évitez d’envoyer des informations sensibles.</p><?php if (isset($errors['description'])): ?><p id="description-error" class="field-error"><?= e($errors['description']) ?></p><?php endif; ?></div>
            <p class="small-note">Les informations saisies servent à traiter votre demande. <a href="mentions-legales.php#donnees">En savoir plus sur vos données.</a></p>
            <button type="submit" class="button button-wide">Envoyer mon message <span aria-hidden="true">↗</span></button>
        </form>
    </section>
</div>
<?php renderFooter($pdo); ?>
