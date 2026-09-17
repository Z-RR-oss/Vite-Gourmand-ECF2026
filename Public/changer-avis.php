<?php

session_start();
require_once '../Config/database.php';


// 1. Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// 2. Autoriser uniquement admin et employé
if (
    $_SESSION['role'] !== 'admin'
    && $_SESSION['role'] !== 'employe'
) {
    exit("Accès refusé.");
}


// 3. Récupérer l'id de l'avis
$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    exit("Avis invalide.");
}

$id = (int) $id;


// 4. Récupérer le nouveau statut
$statut = $_GET['statut'] ?? null;

if (!$statut) {
    exit("Statut manquant.");
}

$statut = trim($statut);


// 5. Vérifier que le statut demandé est autorisé
$statutsAutorises = [
    'validé',
    'refusé'
];

if (!in_array($statut, $statutsAutorises, true)) {
    exit("Statut invalide.");
}


// 6. Vérifier que l'avis existe
$sqlAvis = "
    SELECT
        id,
        statut_validation
    FROM avis
    WHERE id = :id
";

$stmtAvis = $pdo->prepare($sqlAvis);

$stmtAvis->execute([
    ':id' => $id
]);

$avis = $stmtAvis->fetch(PDO::FETCH_ASSOC);

if (!$avis) {
    exit("Avis introuvable.");
}


// 7. Empêcher de retraiter un avis déjà traité
if ($avis['statut_validation'] !== 'en attente') {
    header("Location: admin-avis.php");
    exit;
}


// 8. Mettre à jour le statut de l'avis
$sqlUpdate = "
    UPDATE avis
    SET statut_validation = :statut
    WHERE id = :id
";

$stmtUpdate = $pdo->prepare($sqlUpdate);

$stmtUpdate->execute([
    ':statut' => $statut,
    ':id' => $id
]);


// 9. Retour vers la gestion des avis
header("Location: admin-avis.php");
exit;