<?php

/** Validation et persistance réelles, réservées à une base jetable. */
if (!str_contains((string) getenv('DB_NAME'), '_test_')) {
    fwrite(STDERR, "Une base _test_ est obligatoire.\n");
    exit(2);
}
require __DIR__ . '/../Config/database.php';
require __DIR__ . '/../Services/CatalogueService.php';
require __DIR__ . '/../Services/OrderRules.php';

$checks = 0;
function expect(bool $condition, string $message): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
    echo 'OK ' . $message . "\n";
}
function invalid(callable $action, string $message): void
{
    try {
        $action();
    } catch (DomainException $error) {
        expect(true, $message);
        return;
    }
    expect(false, $message);
}
$menu = ['titre' => 'TEST qualité catalogue', 'description' => 'Description', 'prix' => '120.00',
    'nb_personnes_min' => '4', 'theme' => 'Classique', 'regime' => 'Classique',
    'stock_disponible' => '5', 'delai_commande_heures' => '48', 'actif' => '1'];
$service = new CatalogueService($pdo);
$mid = $pid = null;
$allergenName = 'TEST-QUALITE-' . bin2hex(random_bytes(6));
try {
    foreach (['0', '-1', '1.001', '1e3', '1000000.01', 'NaN', ['1']] as $price) {
        invalid(fn () => validateMenuInput(array_replace($menu, ['prix' => $price])), 'prix invalide refusé');
    }
    foreach (['4oops', '4.5', '-1', ['4']] as $people) {
        invalid(fn () => validateMenuInput(array_replace($menu, ['nb_personnes_min' => $people])), 'entier invalide refusé');
    }
    invalid(fn () => validateMenuInput(array_replace($menu, ['titre' => []])), 'titre tableau refusé');
    $mid = $service->saveMenu($menu);
    expect($service->menu($mid)['titre'] === $menu['titre'], 'création du menu');
    $service->saveMenu(array_replace($menu, ['prix' => '150.50']), $mid);
    expect($service->menu($mid)['prix'] === '150.50', 'modification du prix exact');
    invalid(fn () => $service->saveMenu(array_replace($menu, ['prix' => '0']), $mid), 'modification invalide refusée');
    expect($service->menu($mid)['prix'] === '150.50', 'ancien prix préservé après rejet');
    $dish = ['nom' => 'TEST qualité plat', 'description' => 'Composition', 'type_plat' => 'plat',
        'nouveaux_allergenes' => $allergenName . ', ' . $allergenName];
    $pid = $service->saveDish($dish);
    expect(count($service->dish($pid)['allergenes']) === 1, 'allergène répété lié une seule fois');
    $aid = $service->dish($pid)['allergenes'][0];
    $service->saveDish(array_replace($dish, ['allergenes' => [$aid, $aid], 'type_plat' => 'dessert']), $pid);
    expect(count($service->dish($pid)['allergenes']) === 1, 'doublons anciens/nouveaux supprimés');
    invalid(fn () => $service->saveDish(array_replace($dish, ['allergenes' => ['99999999']]), $pid), 'allergène inexistant refusé');
    expect($service->dish($pid)['type_plat'] === 'dessert' && count($service->dish($pid)['allergenes']) === 1, 'plat et liens préservés après rejet');
    invalid(fn () => validateDishInput(['nom' => '', 'type_plat' => 'autre'], []), 'nom et type obligatoires');
    invalid(fn () => validateDishInput(array_replace($dish, ['allergenes' => '1']), $service->allergens()), 'liste scalaire refusée');
    $service->saveDish(array_replace($dish, ['allergenes' => [], 'nouveaux_allergenes' => '']), $pid);
    expect($service->dish($pid)['allergenes'] === [], 'retrait explicite des associations');
    foreach ([2026, 2027] as $year) {
        $official = array_keys(json_decode(file_get_contents(__DIR__ . '/fixtures/holidays-' . $year . '.json'), true, 512, JSON_THROW_ON_ERROR));
        sort($official);
        expect(metropolitanHolidays($year) === $official, 'calendrier conforme à la fixture officielle ' . $year);
    }
    expect(calculerJoursOuvres('2026-04-03', '2026-04-07') === 1, 'lundi de Pâques exclu');
    expect(calculerJoursOuvres('2026-12-24', '2027-01-04') === 5, 'Noël, nouvel an, week-ends et changement année');
    expect(calculerJoursOuvres('2026-05-04', '2026-05-04') === 0, 'jour de départ exclu');
    expect(calculerJoursOuvres('2026-05-13', '2026-05-15') === 1, 'Ascension exclue');
    expect(calculerJoursOuvres('2026-05-22', '2026-05-26') === 1, 'Pentecôte exclue');
    $input = ['adresse_prestation' => 'Adresse fictive', 'lieu_prestation' => 'Bordeaux',
        'date_prestation' => date('Y-m-d', strtotime('+30 days')), 'heure_prestation' => '12:00',
        'nb_personnes' => '9', 'distance_km' => '20'];
    $data = validateOrderInput($input, $menu);
    expect($data['distance_km'] === 0.0 && $data['frais_livraison'] == 0, 'Bordeaux gratuit imposé par le serveur');
    invalid(fn () => validateOrderInput(array_replace($input, ['lieu_prestation' => 'Mérignac', 'distance_km' => '0']), $menu), 'hors Bordeaux zéro interdit');
    $data = validateOrderInput(array_replace($input, ['lieu_prestation' => 'Mérignac']), $menu);
    expect($data['prix_total'] === 259.8, 'prix hors Bordeaux inchangé');
    // Une erreur SQL après la création du plat doit annuler toute la transaction.
    $pdo->exec("CREATE TRIGGER quality_fail_link BEFORE INSERT ON plat_allergene FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'TEST rollback'");
    $count = $pdo->query('SELECT COUNT(*) FROM plats')->fetchColumn();
    try {
        $service->saveDish(array_replace($dish, ['allergenes' => [$aid], 'nouveaux_allergenes' => '']));
        expect(false, 'erreur SQL attendue');
    } catch (PDOException $error) {
        expect($pdo->query('SELECT COUNT(*) FROM plats')->fetchColumn() === $count && !$pdo->inTransaction(), 'rollback intégral après erreur SQL');
    }
} finally {
    $pdo->exec('DROP TRIGGER IF EXISTS quality_fail_link');
    if ($pid !== null) {
        $pdo->prepare('DELETE FROM plats WHERE id = ?')->execute([$pid]);
    }
    if ($mid !== null) {
        $pdo->prepare('DELETE FROM menus WHERE id = ?')->execute([$mid]);
    }
    $pdo->prepare('DELETE FROM allergenes WHERE nom = ?')->execute([$allergenName]);
}
echo $checks . " contrôles catalogue/calendrier/livraison réussis.\n";
