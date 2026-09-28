<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/OrderService.php';
requireLogin();
requirePost();
$id = positiveId($_POST['id'] ?? null);
try {
    (new OrderService($pdo))->cancel($id, (int) $_SESSION['user_id']);
} catch (DomainException $error) {
    abortRequest(409, $error->getMessage());
}
header('Location: mes-commandes.php', true, 303);
exit;
