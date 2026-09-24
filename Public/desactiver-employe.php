<?php

session_start();
require_once '../Config/database.php';


// Vérifier la connexion
if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
    header("Location: login.php");
    exit;
}


// Seul l'administrateur peut modifier un employé
if ($_SESSION['role'] !== 'admin') {
    exit("Accès refusé.");
}


// Cette page ne doit être appelée qu'en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin-employes.php");
    exit;
}


// Récupérer les informations
$id = $_POST['id'] ?? null;

$action = trim(
    $_POST['action'] ?? ''
);


if (!$id || !is_numeric($id)) {
    exit("Employé invalide.");
}

$id = (int) $id;


if (
    $action !== 'desactiver'
    && $action !== 'activer'
) {
    exit("Action invalide.");
}


// Vérifier que le compte existe
// et qu'il s'agit bien d'un employé
$sqlEmploye = "
    SELECT
        id,
        role,
        actif
    FROM users

    WHERE id = :id
    AND role = 'employe'
";

$stmtEmploye = $pdo->prepare(
    $sqlEmploye
);

$stmtEmploye->execute([
    ':id' => $id
]);

$employe = $stmtEmploye->fetch(
    PDO::FETCH_ASSOC
);


if (!$employe) {
    exit("Compte employé introuvable.");
}


// Déterminer le nouvel état
$nouvelEtat =
    $action === 'activer'
        ? 1
        : 0;


// Mise à jour
$sqlUpdate = "
    UPDATE users

    SET actif = :actif

    WHERE id = :id
    AND role = 'employe'
";

$stmtUpdate = $pdo->prepare(
    $sqlUpdate
);

$stmtUpdate->execute([
    ':actif' => $nouvelEtat,
    ':id' => $id
]);


header(
    "Location: admin-employes.php"
);

exit;