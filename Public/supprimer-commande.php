<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];


// 2. Vérifier l'identifiant
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit("ID de commande invalide.");
}

$id = (int) $id;


// 3. Vérifier que la commande appartient
// bien à l'utilisateur connecté
$sql = "
    SELECT
        id,
        statut
    FROM commandes

    WHERE id = :id
    AND user_id = :user_id
";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    ':id' => $id,
    ':user_id' => $user_id
]);

$commande = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$commande) {
    exit(
        "Commande introuvable ou accès refusé."
    );
}


// 4. Seules les commandes en attente
// peuvent être annulées
if ($commande['statut'] !== 'en attente') {

    exit(
        "Cette commande ne peut plus être annulée car elle a déjà été prise en charge."
    );
}


// 5. Annuler la commande et conserver son historique

try {

    $pdo->beginTransaction();


    $sqlUpdate = "
        UPDATE commandes

        SET statut = 'annulée'

        WHERE id = :id
        AND user_id = :user_id
        AND statut = 'en attente'
    ";

    $stmtUpdate = $pdo->prepare(
        $sqlUpdate
    );

    $stmtUpdate->execute([
        ':id' => $id,
        ':user_id' => $user_id
    ]);


    // 6. Ajouter l'annulation à l'historique

    $sqlHistorique = "
        INSERT INTO historique_statuts (
            commande_id,
            statut,
            modifie_par
        )

        VALUES (
            :commande_id,
            'annulée',
            :modifie_par
        )
    ";

    $stmtHistorique = $pdo->prepare(
        $sqlHistorique
    );

    $stmtHistorique->execute([
        ':commande_id' => $id,
        ':modifie_par' => $user_id
    ]);


    $pdo->commit();


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log(
        "Erreur annulation commande : "
        . $e->getMessage()
    );

    exit(
        "Une erreur est survenue lors de l'annulation de la commande."
    );
}


// 7. Retour vers l'espace utilisateur
header(
    "Location: mes-commandes.php"
);

exit;