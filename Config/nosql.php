<?php

declare(strict_types=1);

/** Server-only Firebase configuration. Environment variables override the local file. */
$localFile = __DIR__ . '/nosql.local.php';
$local = is_file($localFile) ? require $localFile : [];
if (!is_array($local)) {
    throw new RuntimeException('La configuration NoSQL locale doit retourner un tableau.');
}
$defaults = [
    'database_url' => '',
    'service_account_file' => '',
    'database_secret' => '',
    'path' => 'vite_gourmand/statistics_v1',
    'timeout' => 10,
    'force_ipv4' => false,
];
$environment = [
    'database_url' => 'FIREBASE_DATABASE_URL',
    'service_account_file' => 'FIREBASE_SERVICE_ACCOUNT_FILE',
    'database_secret' => 'FIREBASE_DATABASE_SECRET',
    'path' => 'FIREBASE_STATISTICS_PATH',
    'timeout' => 'FIREBASE_TIMEOUT',
    'force_ipv4' => 'FIREBASE_FORCE_IPV4',
];
$config = array_replace($defaults, $local);
foreach ($environment as $key => $variable) {
    $value = getenv($variable);
    if ($value !== false) {
        $config[$key] = $value;
    }
}
return $config;
