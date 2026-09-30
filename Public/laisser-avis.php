<?php

require_once __DIR__ . '/../Config/database.php';

requireLogin();

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit('Commande invalide.');
}

$id = (int) $id;

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
    exit('Commande introuvable.');
}

if ($commande['statut'] !== 'terminée') {
    exit(
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
    exit(
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
    <form method="post">
        <?= csrfInput() ?>
        <label for="note">
            Note
        </label>
        <select
            id="note"
            name="note"
            required
        >
            <option value="">
                Choisir une note
            </option>
            <option value="1">
                1 / 5
            </option>
            <option value="2">
                2 / 5
            </option>
            <option value="3">
                3 / 5
            </option>
            <option value="4">
                4 / 5
            </option>
            <option value="5">
                5 / 5
            </option>
        </select>
        <br><br>
        <label for="commentaire">
            Commentaire
        </label>
        <textarea
            id="commentaire"
            name="commentaire"
            rows="5"
            cols="40"
            required
        ></textarea>
        <br><br>
        <button type="submit">
            Envoyer mon avis
        </button>
    </form>
<?php renderFooter($pdo); ?>
