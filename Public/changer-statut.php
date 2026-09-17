<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// 2. Seuls un employé ou un administrateur
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


// 4. Vérifier l'identifiant de la commande
if (!$id || !is_numeric($id)) {
    exit("ID de commande invalide.");
}

$id = (int) $id;


// 5. Vérifier le statut demandé
if (!$statut) {
    exit("Statut manquant.");
}

$statut = strtolower(trim($statut));


// Statuts prévus par le sujet Studi
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
    SELECT id, statut
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


// Si le statut est déjà le même,
// inutile de créer une nouvelle ligne d'historique
if ($commande['statut'] === $statut) {
    header("Location: admin-commandes.php");
    exit;
}


try {

    // Les deux opérations doivent réussir ensemble
    $pdo->beginTransaction();


    // 7. Modifier le statut actuel de la commande
    $sqlUpdate = "
        UPDATE commandes
        SET statut = :statut
        WHERE id = :id
    ";

    $stmtUpdate = $pdo->prepare($sqlUpdate);

    $stmtUpdate->execute([
        ':statut' => $statut,
        ':id' => $id
    ]);


    // 8. Enregistrer le changement dans l'historique
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

    $stmtHistorique = $pdo->prepare($sqlHistorique);

    $stmtHistorique->execute([
        ':commande_id' => $id,
        ':statut' => $statut,
        ':modifie_par' => $_SESSION['user_id']
    ]);


    // 9. Valider les deux opérations
    $pdo->commit();


    // 10. Retour à la liste des commandes
    header("Location: admin-commandes.php");
    exit;


} catch (PDOException $e) {

    // Annuler les modifications si une opération échoue
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());

    exit("Une erreur est survenue lors du changement de statut.");
}