<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$databaseConfig = is_file(__DIR__ . '/database.local.php')
    ? require __DIR__ . '/database.local.php' : [];
$databaseConfig = is_array($databaseConfig) ? $databaseConfig : [];
// L'environnement prime sur le fichier privé : la recette peut utiliser une base isolée.
$databaseValue = static function (string $key, string $default = '') use ($databaseConfig): string {
    $value = getenv($key);
    return $value !== false ? $value : (string) ($databaseConfig[$key] ?? $default);
};

try {
    $host = $databaseValue('DB_HOST', 'localhost');
    $port = $databaseValue('DB_PORT', '3306');
    $name = $databaseValue('DB_NAME', 'vite_gourmand');
    $socket = $databaseValue('DB_SOCKET');
    $dsn = $socket !== '' ? "mysql:unix_socket=$socket;dbname=$name;charset=utf8mb4"
        : "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
    $pdo = new PDO($dsn, $databaseValue('DB_USER', 'root'), $databaseValue('DB_PASSWORD'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $timezone = $databaseValue('DB_TIMEZONE');
    if ($timezone !== '') {
        $timezoneStatement = $pdo->prepare('SET time_zone = ?');
        $timezoneStatement->execute([$timezone]);
    }
    refreshAuthentication($pdo);
} catch (PDOException $exception) {
    error_log('Connexion base de données indisponible (code ' . $exception->getCode() . ').');
    if (PHP_SAPI === 'cli') {
        throw new RuntimeException('Base de données indisponible.', 0, $exception);
    }
    abortRequest(503, 'Le service est temporairement indisponible. Réessayez dans quelques instants.');
}
