<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/FormValidation.php';
require_once __DIR__ . '/../Templates/layout.php';
requireAdminOrEmployee();
requireFormMethod();
$joursAutorises = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
$jour = inputText($_GET, 'jour');
if (!in_array($jour, $joursAutorises, true)) {
    abortRequest(400, 'Jour invalide.');
}
$query = $pdo->prepare('SELECT * FROM horaires WHERE jour = ?');
$query->execute([$jour]);
$horaire = $query->fetch(PDO::FETCH_ASSOC);
$heureOuverture = $horaire['heure_ouverture'] ?? '09:00';
$heureFermeture = $horaire['heure_fermeture'] ?? '18:00';
$ferme = (int) ($horaire['ferme'] ?? 0);
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'enregistrer';
    if (!in_array($action, ['enregistrer', 'supprimer'], true)) {
        abortRequest(400, 'Action invalide.');
    }
    if ($action === 'supprimer') {
        $pdo->prepare('DELETE FROM horaires WHERE jour = ?')->execute([$jour]);
        header('Location: admin-horaires.php', true, 303);
        exit;
    }
    $ferme = isset($_POST['ferme']) ? 1 : 0;
    $heureOuverture = inputText($_POST, 'heure_ouverture');
    $heureFermeture = inputText($_POST, 'heure_fermeture');
    if (!$ferme) {
        $format = '/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D';
        if (!preg_match($format, $heureOuverture) || !preg_match($format, $heureFermeture)) {
            $erreur = 'Indiquez deux heures valides au format HH:MM.';
        } elseif ($heureFermeture <= $heureOuverture) {
            $erreur = 'La fermeture doit être après l’ouverture.';
        }
    } else {
        // Un jour fermé ne doit pas conserver d'anciennes heures d'ouverture.
        $heureOuverture = $heureFermeture = '00:00';
    }
    if ($erreur === '') {
        $query = $pdo->prepare('INSERT INTO horaires (jour, heure_ouverture, heure_fermeture, ferme)
            VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE heure_ouverture = VALUES(heure_ouverture),
            heure_fermeture = VALUES(heure_fermeture), ferme = VALUES(ferme)');
        $query->execute([$jour, $heureOuverture, $heureFermeture, $ferme]);
        header('Location: admin-horaires.php', true, 303);
        exit;
    }
    http_response_code(422);
}

renderHeader('Modifier un horaire');
?>
<section class="content-panel">
    <h1>Horaires du <?= e($jour) ?></h1>
    <p class="info">Modifiez les horaires de ce jour puis cliquez sur <strong>Enregistrer les horaires</strong>.</p>
    <?php if ($erreur !== ''): ?>
        <p class="erreur" role="alert"><?= e($erreur) ?></p>
    <?php endif; ?>
    <form method="post">
        <?= csrfInput() ?>
        <p id="hours-help" class="small-note">Renseignez les deux heures si l’établissement est ouvert, ou cochez « Fermé ce jour ».</p>
        <label for="heure_ouverture">Heure d'ouverture</label>
        <input type="time" id="heure_ouverture" name="heure_ouverture" aria-describedby="hours-help"
            value="<?= e(substr($heureOuverture, 0, 5)) ?>">
        <label for="heure_fermeture">Heure de fermeture</label>
        <input type="time" id="heure_fermeture" name="heure_fermeture" aria-describedby="hours-help"
            value="<?= e(substr($heureFermeture, 0, 5)) ?>">
        <div class="checkbox">
            <input type="checkbox" id="ferme" name="ferme" <?= $ferme === 1 ? 'checked' : '' ?>>
            <label for="ferme">Fermé ce jour</label>
        </div>
        <button class="enregistrer" type="submit" name="action" value="enregistrer">Enregistrer les horaires</button>
        <?php if ($horaire): ?>
            <button class="supprimer" type="submit" name="action" value="supprimer">Supprimer cet horaire</button>
        <?php endif; ?>
    </form>
    <a class="retour" href="admin-horaires.php">← Retour à la gestion des horaires</a>
</section>
<?php renderFooter($pdo); ?>
