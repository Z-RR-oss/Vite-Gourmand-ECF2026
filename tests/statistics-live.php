<?php

declare(strict_types=1);

require_once __DIR__ . '/../Services/StatisticsService.php';

if (getenv('RUN_FIREBASE_TEST') !== '1') {
    fwrite(STDERR, "NON EXÉCUTÉ : configurez Firebase puis RUN_FIREBASE_TEST=1 pour tester une lecture distante réelle.\n");
    exit(77);
}
try {
    $repository = new StatisticsRepository(require __DIR__ . '/../Config/nosql.php');
    $view = (new StatisticsService($repository))->dashboard(StatisticsService::parseFilters(['periode' => 'tout']));
    echo 'Lecture réelle Firebase réussie : ' . count($view['rows']) . ' menus ; synchronisation ' . $view['synced_at'] . ".\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "ÉCHEC : lecture réelle Firebase impossible. Vérifiez configuration, droits et synchronisation.\n");
    exit(1);
}
