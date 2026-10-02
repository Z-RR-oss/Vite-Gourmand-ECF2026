<?php

require_once __DIR__ . '/../Config/database.php';
requireRole('admin');
requirePost();
$id = positiveId($_POST['id'] ?? null);
$action = $_POST['action'] ?? '';
if (!in_array($action, ['activer', 'desactiver'], true)) {
    abortRequest(400, 'Action invalide.');
}
$query = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'employe'");
$query->execute([$id]);
if (!$query->fetchColumn()) {
    abortRequest(404, 'Compte employé introuvable.');
}
// Le filtre de rôle reste aussi dans l'écriture : un administrateur ne peut pas être ciblé.
$query = $pdo->prepare("UPDATE users SET actif = ? WHERE id = ? AND role = 'employe'");
$query->execute([$action === 'activer' ? 1 : 0, $id]);
header('Location: admin-employes.php', true, 303);
exit;
