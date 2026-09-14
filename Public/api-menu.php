<?php

require_once '../Config/database.php';

$search = $_GET['search'] ?? '';
$prixMin = $_GET['prix_min'] ?? '';
$prixMax = $_GET['prix_max'] ?? '';
$theme = $_GET['theme'] ?? '';
$regime = $_GET['regime'] ?? '';
$personnes = $_GET['personnes'] ?? '';

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = "titre LIKE :search";
    $params[':search'] = "%$search%";
}

if ($theme !== '') {
    $conditions[] = "theme = :theme";
    $params[':theme'] = $theme;
}

if ($regime !== '') {
    $conditions[] = "regime = :regime";
    $params[':regime'] = $regime;
}

if ($prixMin !== '') {
    $conditions[] = "prix >= :prix_min";
    $params[':prix_min'] = $prixMin;
}

if ($prixMax !== '') {
    $conditions[] = "prix <= :prix_max";
    $params[':prix_max'] = $prixMax;
}

if ($personnes !== '') {
    $conditions[] = "nb_personnes_min <= :personnes";
    $params[':personnes'] = $personnes;
}

$sql = "SELECT * FROM menus";

if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($menus);