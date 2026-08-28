<?php

require_once '../Config/database.php';

$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM menus WHERE titre LIKE :search";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(':search', "%$search%");

$stmt->execute();

$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($menus);

