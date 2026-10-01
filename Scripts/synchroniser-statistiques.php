<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../Services/StatisticsService.php';

// Une seule synchronisation par serveur ; garder le verrou jusqu'à la fin de l'écriture Firebase.
$lock = fopen(sys_get_temp_dir() . '/vite-gourmand-stats-' . hash('sha256', __DIR__) . '.lock', 'c');
if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Une synchronisation est déjà en cours.\n");
    exit(2);
}

try {
    $repository = new StatisticsRepository(require __DIR__ . '/../Config/nosql.php');
    require __DIR__ . '/../Config/database.php';
    $snapshot = (new StatisticsService($repository))->synchronize($pdo);
    fwrite(STDOUT, 'Synchronisation Firebase réussie : ' . count($snapshot['menus']) . ' menus, '
        . count($snapshot['days']) . ' jours ; ' . $snapshot['synced_at'] . ".\n");
} catch (Throwable $exception) {
    // Ne jamais afficher configuration, réponse brute du fournisseur, jetons ou URLs complètes.
    error_log('Statistics synchronization failed [' . get_class($exception) . '].');
    fwrite(STDERR, "Échec de synchronisation. Vérifiez la configuration SQL/Firebase et les accès réseau.\n");
    exit(1);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
