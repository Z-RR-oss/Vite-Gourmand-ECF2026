<?php

session_start();
require_once '../Config/database.php';


// --------------------------------------------------
// 1. VÉRIFIER LA CONNEXION
// --------------------------------------------------

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");
    exit;
}


$user_id =
    (int) $_SESSION['user_id'];


// --------------------------------------------------
// 2. VÉRIFIER L'IDENTIFIANT
// --------------------------------------------------

$id =
    $_GET['id']
    ?? null;


if (!$id || !is_numeric($id)) {

    exit(
        "ID de commande invalide."
    );
}


$id = (int) $id;


// --------------------------------------------------
// 3. ANNULATION
// --------------------------------------------------

try {

    $pdo->beginTransaction();


    /*
     * On verrouille la commande pendant
     * l'annulation pour éviter qu'elle
     * soit annulée deux fois simultanément.
     */
    $sqlCommande = "
        SELECT
            id,
            user_id,
            menu_id,
            statut

        FROM commandes

        WHERE id = :id
        AND user_id = :user_id

        FOR UPDATE
    ";


    $stmtCommande =
        $pdo->prepare(
            $sqlCommande
        );


    $stmtCommande->execute([
        ':id' =>
            $id,

        ':user_id' =>
            $user_id
    ]);


    $commande =
        $stmtCommande->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$commande) {

        throw new Exception(
            "Commande introuvable ou accès refusé."
        );
    }


    /*
     * Le client ne peut annuler
     * que tant que la commande
     * est encore en attente.
     */
    if (
        $commande['statut']
        !== 'en attente'
    ) {

        throw new Exception(
            "Cette commande ne peut plus être annulée car elle a déjà été prise en charge."
        );
    }


    // --------------------------------------------------
    // 4. PASSER LA COMMANDE À ANNULÉE
    // --------------------------------------------------

    $sqlUpdate = "
        UPDATE commandes

        SET statut = 'annulée'

        WHERE id = :id
        AND user_id = :user_id
        AND statut = 'en attente'
    ";


    $stmtUpdate =
        $pdo->prepare(
            $sqlUpdate
        );


    $stmtUpdate->execute([
        ':id' =>
            $id,

        ':user_id' =>
            $user_id
    ]);


    if (
        $stmtUpdate->rowCount()
        !== 1
    ) {

        throw new Exception(
            "La commande ne peut plus être annulée."
        );
    }


    // --------------------------------------------------
    // 5. RENDRE UNE DISPONIBILITÉ AU MENU
    // --------------------------------------------------

    $sqlStock = "
        UPDATE menus

        SET stock_disponible =
            stock_disponible + 1

        WHERE id = :menu_id
    ";


    $stmtStock =
        $pdo->prepare(
            $sqlStock
        );


    $stmtStock->execute([
        ':menu_id' =>
            $commande['menu_id']
    ]);


    if (
        $stmtStock->rowCount()
        !== 1
    ) {

        throw new Exception(
            "Impossible de restaurer le stock du menu."
        );
    }


    // --------------------------------------------------
    // 6. HISTORIQUE
    // --------------------------------------------------

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


    $stmtHistorique =
        $pdo->prepare(
            $sqlHistorique
        );


    $stmtHistorique->execute([
        ':commande_id' =>
            $id,

        ':modifie_par' =>
            $user_id
    ]);


    $pdo->commit();


} catch (Throwable $e) {


    if ($pdo->inTransaction()) {

        $pdo->rollBack();
    }


    error_log(
        "Erreur annulation commande : "
        . $e->getMessage()
    );


    exit(
        $e->getMessage()
    );
}


// --------------------------------------------------
// 7. RETOUR ESPACE CLIENT
// --------------------------------------------------

header(
    "Location: mes-commandes.php"
);

exit;