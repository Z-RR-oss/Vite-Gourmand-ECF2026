<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Services/CatalogueService.php';
require_once __DIR__ . '/../Templates/layout.php';
require_once __DIR__ . '/../Templates/forms/helpers.php';

requireAdminOrEmployee();
requireFormMethod();
$service = new CatalogueService($pdo);
$id = $editing ? positiveId($_GET['id'] ?? null) : null;
$dish = $id === null ? ['allergenes' => []] : $service->dish($id);
if ($dish === null) {
    abortRequest(404, 'Plat introuvable.');
}
$allergens = $service->allergens();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nom', 'description', 'type_plat', 'nouveaux_allergenes'] as $field) {
        $dish[$field] = inputText($_POST, $field);
    }
    $dish['allergenes'] = is_array($_POST['allergenes'] ?? null) ? array_map('strval', $_POST['allergenes']) : [];
    try {
        $service->saveDish($_POST, $id);
        header('Location: admin-plats.php', true, 303);
        exit;
    } catch (FormValidationException $error) {
        $errors = $error->errors;
        http_response_code(422);
    } catch (DomainException $error) {
        abortRequest(404, $error->getMessage());
    }
}
$title = $editing ? 'Modifier le plat' : 'Ajouter un plat';
renderHeader($title);
require __DIR__ . '/../Templates/forms/dish.php';
renderFooter($pdo);
