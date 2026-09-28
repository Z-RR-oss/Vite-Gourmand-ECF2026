<?php
require_once __DIR__ . '/../Config/database.php';
requireAdminOrEmployee();
requirePost();
$id = positiveId($_POST['id'] ?? null);
$status = $_POST['statut'] ?? '';
if (!in_array($status, ['validé', 'refusé'], true)) {
    abortRequest(400, 'Statut invalide.');
}
$stmt = $pdo->prepare("UPDATE avis SET statut_validation = ? WHERE id = ? AND statut_validation = 'en attente'");
$stmt->execute([$status, $id]);
header('Location: admin-avis.php', true, 303);
exit;
