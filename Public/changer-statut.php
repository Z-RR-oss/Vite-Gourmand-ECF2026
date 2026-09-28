<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/OrderService.php';
require_once __DIR__ . '/../Services/OrderNotifications.php';
requireAdminOrEmployee();
requirePost();
$id = positiveId($_POST['id'] ?? null);
try {
    $status = trim($_POST['statut'] ?? '');
    (new OrderService($pdo))->transition($id, (int) $_SESSION['user_id'], $status);
    notifyOrderStatus($pdo, $id, $status);
} catch (DomainException $error) {
    abortRequest(409, $error->getMessage());
}
header('Location: admin-commandes.php', true, 303);
exit;
