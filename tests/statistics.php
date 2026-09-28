<?php
declare(strict_types=1);

require_once __DIR__ . '/../Services/StatisticsService.php';

$checks = 0;
function statsCheck(bool $condition, string $message): void
{
    global $checks;
    $checks++;
    if (!$condition) {
        throw new RuntimeException('ÉCHEC : ' . $message);
    }
}
function statsReject(callable $call, string $message): void
{
    try {
        $call();
    } catch (RuntimeException | InvalidArgumentException $exception) {
        statsCheck(true, $message);
        return;
    }
    statsCheck(false, $message);
}

$configuration = ['database_url' => 'https://test-only.firebaseio.com', 'database_secret' => 'TEST_ONLY_NOT_A_CREDENTIAL'];
$fixedDate = new DateTimeImmutable('2026-09-28', new DateTimeZone('Europe/Paris'));
$filters = StatisticsService::parseFilters(['periode' => '7'], $fixedDate);
statsCheck($filters['du'] === '2026-09-22' && $filters['au'] === '2026-09-28', 'Période inclusive de sept jours');
statsReject(fn() => StatisticsService::parseFilters(['periode' => 'invalide']), 'Période non autorisée');
statsReject(fn() => StatisticsService::parseFilters(['menu' => ['1']]), 'Menu tableau rejeté');
statsReject(fn() => StatisticsService::parseFilters(['menu' => '1 OR 1=1']), 'Menu injection rejeté');
statsReject(fn() => StatisticsService::parseFilters(['periode' => 'personnalisee', 'du' => '2026-02-30', 'au' => '2026-03-01']), 'Date impossible rejetée');
statsReject(fn() => StatisticsService::parseFilters(['periode' => 'personnalisee', 'du' => '2026-10-01', 'au' => '2026-09-01']), 'Dates inversées rejetées');
statsReject(fn() => new StatisticsRepository([]), 'Configuration manquante rejetée');
statsReject(fn() => new StatisticsRepository(['database_url' => 'http://test-only.firebaseio.com'] + $configuration), 'HTTPS obligatoire');
statsReject(fn() => new StatisticsRepository(['database_url' => 'https://example.org'] + $configuration), 'Domaine non Firebase rejeté');
statsReject(fn() => new StatisticsRepository(['path' => '../other'] + $configuration), 'Chemin invalide rejeté');

$snapshot = StatisticsService::buildSnapshot([
    ['id' => 1, 'titre' => 'Menu Classique'], ['id' => 2, 'titre' => 'Menu Vegan'], ['id' => 3, 'titre' => 'Menu sans commande'],
], [
    ['day' => '2026-09-21', 'menu_id' => 1, 'commandes' => 1, 'annulees' => 0, 'personnes' => 4, 'ca_centimes' => 10000],
    ['day' => '2026-09-22', 'menu_id' => 1, 'commandes' => 2, 'annulees' => 1, 'personnes' => 10, 'ca_centimes' => 12059],
    ['day' => '2026-09-28', 'menu_id' => 2, 'commandes' => 1, 'annulees' => 0, 'personnes' => 6, 'ca_centimes' => 25000],
    ['day' => '2026-09-29', 'menu_id' => 2, 'commandes' => 1, 'annulees' => 0, 'personnes' => 6, 'ca_centimes' => 5000],
], '2026-09-28T12:00:00+00:00');
$view = StatisticsService::summarize($snapshot, $filters);
statsCheck($view['totals']['commandes'] === 3, 'Bornes dates incluses et dates extérieures exclues');
statsCheck($view['totals']['ca_centimes'] === 37059, 'Addition des centimes sans perte de précision');
statsCheck($view['totals']['annulees'] === 1, 'Annulations séparées');
statsCheck($view['totals']['personnes'] === 16, 'Nombre de convives');
statsCheck(count($view['rows']) === 3 && $view['rows'][2]['commandes'] === 0, 'Menus sans commandes conservés');
$oneMenu = StatisticsService::summarize($snapshot, $filters + []);
$menuFilters = StatisticsService::parseFilters(['periode' => '7', 'menu' => '2'], $fixedDate);
$oneMenu = StatisticsService::summarize($snapshot, $menuFilters);
statsCheck(count($oneMenu['rows']) === 1 && $oneMenu['totals']['ca_centimes'] === 25000, 'Filtre menu et dates combinés');
statsReject(fn() => StatisticsService::summarize($snapshot, array_replace($filters, ['menu' => 99])), 'Menu inconnu rejeté');
$broken = $snapshot;
$broken['days']['2026-09-22']['menu_1']['ca_centimes'] = -100;
statsReject(fn() => StatisticsService::summarize($broken, $filters), 'Agrégat négatif rejeté');

$requests = [];
$repository = new StatisticsRepository($configuration, static function ($method, $url, $headers, $body, $timeout) use (&$requests, $snapshot): array {
    $requests[] = [$method, $url, $body];
    statsCheck($timeout === 10, 'Timeout transport');
    return $method === 'PUT' ? ['status' => 204, 'body' => ''] : ['status' => 200, 'body' => json_encode($snapshot)];
});
$repository->publish($snapshot);
$repository->publish($snapshot);
$fromFirebase = (new StatisticsService($repository))->dashboard($filters);
statsCheck($requests[0][0] === 'PUT' && $requests[0][2] === $requests[1][2], 'Synchronisation idempotente par remplacement');
statsCheck($requests[2][0] === 'GET' && $fromFirebase['totals']['commandes'] === 3, 'Dashboard lit le transport Firebase');
foreach ([['status' => 401, 'body' => 'TEST_ONLY_SECRET'], ['status' => 500, 'body' => 'server'], ['status' => 200, 'body' => 'broken'], ['status' => 200, 'body' => 'null'], ['status' => 200, 'body' => '{}']] as $response) {
    statsReject(fn() => (new StatisticsRepository($configuration, fn() => $response))->read(), 'Erreur fournisseur gérée');
}
try {
    (new StatisticsRepository($configuration, static function (): never { throw new RuntimeException('TEST_ONLY_SECRET'); }))->read();
    statsCheck(false, 'Erreur réseau attendue');
} catch (RuntimeException $exception) {
    statsCheck(!str_contains($exception->getMessage(), 'TEST_ONLY_SECRET'), 'Exception transport expurgée');
}

// A temporary test key exercises JWT signing and OAuth. No external network call occurs.
$key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
openssl_pkey_export($key, $privateKey);
$keyDetails = openssl_pkey_get_details($key);
$keyFile = tempnam(sys_get_temp_dir(), 'vg-test-account-');
file_put_contents($keyFile, json_encode(['type' => 'service_account', 'client_email' => 'test@example.org', 'private_key' => $privateKey]));
$oauthCalls = 0;
try {
    $oauthRepository = new StatisticsRepository($configuration + ['service_account_file' => $keyFile], static function ($method, $url, $headers, $body) use (&$oauthCalls, $keyDetails, $snapshot): array {
        if ($url === 'https://oauth2.googleapis.com/token') {
            $oauthCalls++;
            parse_str($body, $form);
            statsCheck($method === 'POST' && $form['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer', 'Échange OAuth JWT');
            [$header, $claims, $signature] = explode('.', $form['assertion']);
            $decoded = json_decode(base64_decode(strtr($claims, '-_', '+/')), true);
            statsCheck($decoded['aud'] === 'https://oauth2.googleapis.com/token' && $decoded['exp'] - $decoded['iat'] === 3600, 'Audience et durée JWT');
            statsCheck(openssl_verify($header . '.' . $claims, base64_decode(strtr($signature, '-_', '+/')), $keyDetails['key'], OPENSSL_ALGO_SHA256) === 1, 'Signature RS256 vérifiée');
            return ['status' => 200, 'body' => json_encode(['access_token' => 'TEST_ONLY_TOKEN', 'expires_in' => 3600])];
        }
        statsCheck(in_array('Authorization: Bearer TEST_ONLY_TOKEN', $headers, true), 'Token en en-tête Bearer');
        statsCheck(!str_contains($url, 'TEST_ONLY_TOKEN') && !str_contains($url, 'auth='), 'Aucun token OAuth dans URL');
        return ['status' => 200, 'body' => json_encode($snapshot)];
    });
    $oauthRepository->read();
    $oauthRepository->read();
    statsCheck($oauthCalls === 1, 'Réutilisation du token seulement en mémoire');
} finally {
    unlink($keyFile);
}

// SQL aggregation test against isolated in-memory fixtures, explicitly not a live Firebase test.
$pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE TABLE menus (id INTEGER, titre TEXT)');
$pdo->exec('CREATE TABLE commandes (menu_id INTEGER, statut TEXT, nb_personnes INTEGER, prix_total NUMERIC, created_at TEXT)');
$pdo->exec("INSERT INTO menus VALUES (1, 'Classique'), (2, 'Vegan')");
$insert = $pdo->prepare('INSERT INTO commandes VALUES (?, ?, ?, ?, ?)');
foreach ([['livré', 120.59], ['terminée', 130], ['en attente du retour de matériel', 140], ['en attente', 150], ['accepté', 160], ['annulée', 170]] as [$status, $price]) {
    $insert->execute([1, $status, 4, $price, '2026-09-28 12:00:00']);
}
$published = null;
$sqlRepository = new StatisticsRepository($configuration, static function ($method, $url, $headers, $body) use (&$published): array {
    $published = json_decode($body, true);
    return ['status' => 204, 'body' => ''];
});
(new StatisticsService($sqlRepository))->synchronize($pdo);
$aggregate = $published['days']['2026-09-28']['menu_1'];
statsCheck($aggregate['commandes'] === 5 && $aggregate['annulees'] === 1, 'SQL sépare annulations des autres commandes');
statsCheck($aggregate['ca_centimes'] === 39059, 'SQL CA seulement livré, attente retour et terminé');
statsCheck($aggregate['personnes'] === 20, 'SQL convives hors annulations');
statsCheck(isset($published['menus']['menu_2']), 'Catalogue complet dans Firebase');

echo $checks . " vérifications statistiques réussies (transport simulé, SQLite en mémoire ; aucun test Firebase distant).\n";
