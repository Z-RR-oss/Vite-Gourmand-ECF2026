<?php

/** Banc axe local : aucune requête ni donnée n'est envoyée à un service d'audit tiers. */
if (PHP_SAPI !== 'cli-server' || !str_contains((string) getenv('DB_NAME'), '_test_')
    || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit;
}
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$assets = ['/__audit/axe.js' => __DIR__ . '/../var/tools/axe.min.js',
    '/__audit/runner.js' => __DIR__ . '/axe-runner.js'];
if (isset($assets[$path])) {
    if (!is_file($assets[$path])) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: application/javascript; charset=utf-8');
    readfile($assets[$path]);
    exit;
}
if (preg_match('~^/[a-z-]+\.php$~D', $path) && is_file(__DIR__ . '/../Public' . $path)) {
    ob_start(static function (string $html): string {
        return str_replace('</body>', '<script src="/__audit/axe.js"></script><script src="/__audit/runner.js"></script></body>', $html);
    });
    require __DIR__ . '/../Public' . $path;
    return true;
}
return false;
