<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}


// 2. Récupérer l'id de la commande
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit("Commande invalide.");
}

$id = (int) $id;


// 3. Vérifier que la commande appartient bien à l'utilisateur
$sql = "
    SELECT
        commandes.*,
        menus.titre
    FROM commandes

    INNER JOIN menus
        ON commandes.menu_id = menus.id

    WHERE commandes.id = :id
    AND commandes.user_id = :user_id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id,
    ':user_id' => $_SESSION['user_id']
]);

$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    exit("Commande introuvable.");
}


// 4. Vérifier que la commande est terminée
if ($commande['statut'] !== 'terminée') {
    exit(
        "Vous pourrez laisser un avis lorsque la commande sera terminée."
    );
}


// 5. Vérifier qu'un avis n'existe pas déjà
$sqlAvis = "
    SELECT id
    FROM avis
    WHERE commande_id = :commande_id
";

$stmtAvis = $pdo->prepare($sqlAvis);

$stmtAvis->execute([
    ':commande_id' => $id
]);

$avisExistant = $stmtAvis->fetch(PDO::FETCH_ASSOC);

if ($avisExistant) {
    exit(
        "Vous avez déjà laissé un avis pour cette commande."
    );
}


// 6. Traiter le formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $note = (int) ($_POST['note'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');


    // Vérifier la note
    if ($note < 1 || $note > 5) {
        exit(
            "La note doit être comprise entre 1 et 5."
        );
    }


    // Vérifier le commentaire
    if ($commentaire === '') {
        exit(
            "Le commentaire est obligatoire."
        );
    }


    // 7. Enregistrer l'avis
    $sqlInsertAvis = "
        INSERT INTO avis (
            commande_id,
            user_id,
            note,
            commentaire,
            statut_validation
        )
        VALUES (
            :commande_id,
            :user_id,
            :note,
            :commentaire,
            'en attente'
        )
    ";

    $stmtInsertAvis = $pdo->prepare(
        $sqlInsertAvis
    );

    $stmtInsertAvis->execute([
        ':commande_id' => $id,
        ':user_id' => $_SESSION['user_id'],
        ':note' => $note,
        ':commentaire' => $commentaire
    ]);


    // 8. Retourner vers les commandes
    header(
        "Location: mes-commandes.php"
    );

    exit;
}

?>

<!DOCTYPE html>

<html lang="fr">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Laisser un avis - Vite & Gourmand
    </title>

</head>


<body>

    <h1>
        Laisser un avis
    </h1>


    <p>
        Menu :
        <?php
        echo htmlspecialchars(
            $commande['titre']
        );
        ?>
    </p>


    <p>
        Commande n°
        <?php
        echo (int) $commande['id'];
        ?>
    </p>


    <form method="post">

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

</body>

</html>