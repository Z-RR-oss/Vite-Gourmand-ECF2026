<?php

require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Templates/catalogue.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    echo json_encode(catalogueMenus($pdo, catalogueFilters($_GET)), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('Catalogue indisponible : ' . $exception->getMessage());
    http_response_code(503);
    echo json_encode(['error' => 'Les menus sont momentanément indisponibles. Veuillez réessayer.'], JSON_UNESCAPED_UNICODE);
}
