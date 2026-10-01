<?php

require_once __DIR__ . '/../Config/database.php';

requireAdminOrEmployee();

$joursAutorises = [
    'Lundi',
    'Mardi',
    'Mercredi',
    'Jeudi',
    'Vendredi',
    'Samedi',
    'Dimanche'
];

$jour = trim(
    $_GET['jour'] ?? ''
);

if (
    !in_array(
        $jour,
        $joursAutorises,
        true
    )
) {
    exit('Jour invalide.');
}

$sql = '
    SELECT *
    FROM horaires
    WHERE jour = :jour
';

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':jour' => $jour
]);

$horaire = $stmt->fetch(
    PDO::FETCH_ASSOC
);

$erreur = '';

$heureOuverture =
    $horaire['heure_ouverture']
    ?? '09:00';

$heureFermeture =
    $horaire['heure_fermeture']
    ?? '18:00';

$ferme = $horaire
    ? (int) $horaire['ferme']
    : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action =
        $_POST['action'] ?? 'enregistrer';

    /*
     * SUPPRESSION
     */
    if ($action === 'supprimer') {
        if ($horaire) {
            $sqlDelete = '
                DELETE FROM horaires
                WHERE jour = :jour
            ';

            $stmtDelete = $pdo->prepare(
                $sqlDelete
            );

            $stmtDelete->execute([
                ':jour' => $jour
            ]);
        }

        header(
            'Location: admin-horaires.php'
        );

        exit;
    }

    /*
     * CRÉATION / MODIFICATION
     */

    $ferme = isset($_POST['ferme'])
        ? 1
        : 0;

    $heureOuverture = trim(
        $_POST['heure_ouverture'] ?? ''
    );

    $heureFermeture = trim(
        $_POST['heure_fermeture'] ?? ''
    );

    // Si le restaurant est ouvert,
    // les heures doivent être renseignées
    if ($ferme === 0) {
        if (
            $heureOuverture === ''
            || $heureFermeture === ''
        ) {
            $erreur =
                "Les heures d'ouverture et de fermeture sont obligatoires.";
        } elseif (
            $heureFermeture
            <= $heureOuverture
        ) {
            $erreur =
                "L'heure de fermeture doit être après l'heure d'ouverture.";
        }
    }

    if ($ferme === 0 && (!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $heureOuverture) || !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $heureFermeture))) {
        $erreur = 'Indiquez des heures valides au format HH:MM.';
    }
    if ($ferme === 1) {
        $heureOuverture = $heureFermeture = '';
    }

    if ($erreur === '') {
        $sqlSave = '
            INSERT INTO horaires (
                jour,
                heure_ouverture,
                heure_fermeture,
                ferme
            )

            VALUES (
                :jour,
                :heure_ouverture,
                :heure_fermeture,
                :ferme
            )

            ON DUPLICATE KEY UPDATE

                heure_ouverture =
                    VALUES(heure_ouverture),

                heure_fermeture =
                    VALUES(heure_fermeture),

                ferme =
                    VALUES(ferme)
        ';

        $stmtSave = $pdo->prepare(
            $sqlSave
        );

        $stmtSave->execute([
            ':jour' => $jour,

            ':heure_ouverture' =>
                $heureOuverture !== ''
                    ? $heureOuverture
                    : '00:00',

            ':heure_fermeture' =>
                $heureFermeture !== ''
                    ? $heureFermeture
                    : '00:00',

            ':ferme' => $ferme
        ]);

        header(
            'Location: admin-horaires.php'
        );

        exit;
    }
}

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Modifier un horaire'); ?>
<section class="content-panel">
    <h1>

        Horaires du

        <?= htmlspecialchars($jour) ?>
    </h1>
    <p class="info">

        Modifiez les horaires de ce jour
        puis cliquez sur
        <strong>
            Enregistrer les horaires
        </strong>.

    </p>
    <?php if ($erreur !== ''): ?>
        <p class="erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>
    <form method="POST">
        <?= csrfInput() ?>
        <p id="hours-help" class="small-note">Renseignez les deux heures si l’établissement est ouvert, ou cochez « Fermé ce jour ».</p>
        <label for="heure_ouverture">
            Heure d'ouverture
        </label>
        <input
            type="time"
            id="heure_ouverture"
            aria-describedby="hours-help"
            name="heure_ouverture"
            value="<?= htmlspecialchars(substr($heureOuverture, 0, 5)) ?>"
        >
        <label for="heure_fermeture">
            Heure de fermeture
        </label>
        <input
            type="time"
            id="heure_fermeture"
            aria-describedby="hours-help"
            name="heure_fermeture"
            value="<?= htmlspecialchars(substr($heureFermeture, 0, 5)) ?>"
        >
        <div class="checkbox">
            <input
                type="checkbox"
                id="ferme"
                name="ferme"
                <?php
    if ($ferme === 1) {
        echo 'checked';
    }
?>
            >
            <label for="ferme">
                Fermé ce jour
            </label>
        </div>
        <button
            class="enregistrer"
            type="submit"
            name="action"
            value="enregistrer"
        >
            Enregistrer les horaires
        </button>
        <?php if ($horaire): ?>
            <button
                class="supprimer"
                type="submit"
                name="action"
                value="supprimer"
            >
                Supprimer cet horaire
            </button>
        <?php endif; ?>
    </form>
    <a
        class="retour"
        href="admin-horaires.php"
    >
        ← Retour à la gestion des horaires
    </a>
</section>
<?php renderFooter($pdo); ?>
