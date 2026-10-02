<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/CatalogueService.php';
require_once __DIR__ . '/../Templates/layout.php';
require_once __DIR__ . '/../Templates/forms/helpers.php';

requireAdminOrEmployee();
requireFormMethod();
$service = new CatalogueService($pdo);
$id = $editing ? positiveId($_GET['id'] ?? null) : null;
$menu = $id === null ? ['actif' => 1, 'stock_disponible' => '0', 'delai_commande_heures' => '0'] : $service->menu($id);
if ($menu === null) {
    abortRequest(404, 'Menu introuvable.');
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Préserver les saisies originales en cas d'erreur, sans les convertir en nombres.
    foreach (['titre', 'description', 'prix', 'nb_personnes_min', 'theme', 'regime',
        'stock_disponible', 'conditions_menu', 'delai_commande_heures'] as $field) {
        $menu[$field] = inputText($_POST, $field);
    }
    $menu['actif'] = isset($_POST['actif']) ? 1 : 0;
    try {
        $service->saveMenu($_POST, $id);
        header('Location: admin-menus.php', true, 303);
        exit;
    } catch (FormValidationException $error) {
        $errors = $error->errors;
        http_response_code(422);
    }
}
$title = $editing ? 'Modifier le menu' : 'Ajouter un menu';
renderHeader($title);
require __DIR__ . '/../Templates/forms/menu.php';
renderFooter($pdo);
