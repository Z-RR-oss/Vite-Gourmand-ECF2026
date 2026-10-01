<?php

require_once __DIR__ . '/../Config/database.php';

requireLogin();

$id = $_GET['id'] ?? null;

$id = positiveId($id);

$sql = '
    SELECT
        commandes.*,
        menus.titre
    FROM commandes

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    WHERE commandes.id = :id
    AND commandes.user_id = :user_id
';

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id,
    ':user_id' => $_SESSION['user_id']
]);

$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    abortRequest(404, 'Commande introuvable.');
}

if ($commande['statut'] !== 'terminée') {
    abortRequest(
        409,
        'Vous pourrez laisser un avis lorsque la commande sera terminée.'
    );
}

$sqlAvis = '
    SELECT id
    FROM avis
    WHERE commande_id = :commande_id
';

$stmtAvis = $pdo->prepare($sqlAvis);

$stmtAvis->execute([
    ':commande_id' => $id
]);

$avisExistant = $stmtAvis->fetch(PDO::FETCH_ASSOC);

if ($avisExistant) {
    abortRequest(
        409,
        'Vous avez déjà laissé un avis pour cette commande.'
    );
}

$erreur = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = filter_var($_POST['note'] ?? null, FILTER_VALIDATE_INT);
    $commentaire = trim($_POST['commentaire'] ?? '');
    if ($note === false || $note < 1 || $note > 5 || $commentaire === '' || mb_strlen($commentaire) > 3000) {
        $erreur = 'Indiquez une note entière de 1 à 5 et un commentaire de 1 à 3 000 caractères.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO avis (commande_id, user_id, note, commentaire, statut_validation) VALUES (?, ?, ?, ?, 'en attente')");
            $stmt->execute([$id, $_SESSION['user_id'], $note, $commentaire]);
            header('Location: mes-commandes.php', true, 303);
            exit;
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1062) {
                throw $exception;
            }
            $erreur = 'Vous avez déjà laissé un avis pour cette commande.';
        }
    }
}

?>
<?php require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Laisser un avis'); ?>
    <h1>
        Laisser un avis
    </h1>
    <p>
        Menu :
        <?= htmlspecialchars($commande['titre']) ?>
    </p>
    <p>
        Commande n°
        <?= (int) $commande['id'] ?>
    </p>
    <?php if ($erreur !== ''): ?><p class="erreur" role="alert"><?= e($erreur) ?></p><?php endif; ?>
    <form method="post">
        <?= csrfInput() ?>
        <label for="note">Note <span class="required-label">(obligatoire)</span></label>
        <select
            id="note"
            name="note"
            required
        >
            <option value="">
                Choisir une note
            </option>
            <option value="1" <?= ($_POST['note'] ?? '') === '1' ? 'selected' : '' ?>>
                1 / 5
            </option>
            <option value="2" <?= ($_POST['note'] ?? '') === '2' ? 'selected' : '' ?>>
                2 / 5
            </option>
            <option value="3" <?= ($_POST['note'] ?? '') === '3' ? 'selected' : '' ?>>
                3 / 5
            </option>
            <option value="4" <?= ($_POST['note'] ?? '') === '4' ? 'selected' : '' ?>>
                4 / 5
            </option>
            <option value="5" <?= ($_POST['note'] ?? '') === '5' ? 'selected' : '' ?>>
                5 / 5
            </option>
        </select>
        <br><br>
        <label for="commentaire">Commentaire <span class="required-label">(obligatoire)</span></label>
        <textarea
            id="commentaire"
            name="commentaire"
            rows="5"
            cols="40"
            maxlength="3000"
            aria-describedby="commentaire-help"
            required
        ><?= e($_POST['commentaire'] ?? '') ?></textarea>
        <p id="commentaire-help" class="small-note">De 1 à 3 000 caractères.</p>
        <br><br>
        <button type="submit">
            Envoyer mon avis
        </button>
    </form>
<?php renderFooter($pdo); ?>
