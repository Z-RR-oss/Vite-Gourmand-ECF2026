<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// 2. Seuls les employés et administrateurs
// peuvent modifier le statut d'une commande
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// 3. Récupérer les paramètres
$id = $_GET['id'] ?? null;
$statut = $_GET['statut'] ?? null;


// 4. Vérifier l'identifiant
if (!$id || !is_numeric($id)) {
    exit("ID de commande invalide.");
}

$id = (int) $id;


// 5. Vérifier le statut
if (!$statut) {
    exit("Statut manquant.");
}

$statut = strtolower(trim($statut));


$statutsAutorises = [
    "accepté",
    "en préparation",
    "en cours de livraison",
    "livré",
    "en attente du retour de matériel",
    "terminée"
];


if (!in_array($statut, $statutsAutorises, true)) {
    exit("Statut invalide.");
}


// 6. Vérifier que la commande existe
$sqlCommande = "
    SELECT
        id,
        statut,
        date_debut_attente_retour,
        materiel_retourne
    FROM commandes
    WHERE id = :id
";

$stmtCommande = $pdo->prepare($sqlCommande);

$stmtCommande->execute([
    ':id' => $id
]);

$commande = $stmtCommande->fetch(PDO::FETCH_ASSOC);


if (!$commande) {
    exit("Commande introuvable.");
}


// Éviter de créer deux fois le même historique
if ($commande['statut'] === $statut) {

    header("Location: admin-commandes.php");
    exit;
}


try {

    $pdo->beginTransaction();


    /*
     * 7. Modifier la commande
     *
     * Si elle passe en attente du retour de matériel,
     * on enregistre également la date de début.
     */
    if (
        $statut ===
        'en attente du retour de matériel'
    ) {

        $sqlUpdate = "
            UPDATE commandes

            SET
                statut = :statut,

                date_debut_attente_retour =
                    COALESCE(
                        date_debut_attente_retour,
                        NOW()
                    ),

                materiel_retourne = 0,

                date_retour_materiel = NULL,

                frais_retard_materiel = 0

            WHERE id = :id
        ";

    } else {

        $sqlUpdate = "
            UPDATE commandes

            SET statut = :statut

            WHERE id = :id
        ";
    }


    $stmtUpdate = $pdo->prepare(
        $sqlUpdate
    );

    $stmtUpdate->execute([
        ':statut' => $statut,
        ':id' => $id
    ]);


    // 8. Ajouter le changement à l'historique
    $sqlHistorique = "
        INSERT INTO historique_statuts (
            commande_id,
            statut,
            modifie_par
        )

        VALUES (
            :commande_id,
            :statut,
            :modifie_par
        )
    ";

    $stmtHistorique = $pdo->prepare(
        $sqlHistorique
    );

    $stmtHistorique->execute([
        ':commande_id' => $id,
        ':statut' => $statut,
        ':modifie_par' => $_SESSION['user_id']
    ]);


    // 9. Tout s'est bien passé
    $pdo->commit();


    header("Location: admin-commandes.php");
    exit;


} catch (PDOException $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    error_log(
        "Erreur changement statut commande : "
        . $e->getMessage()
    );


    exit(
        "Une erreur est survenue lors du changement de statut."
    );
}