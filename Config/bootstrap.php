<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/auth.php';
date_default_timezone_set('Europe/Paris');
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(static function (Throwable $exception): void {
    error_log((string) $exception);
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Une erreur est survenue. Consultez les journaux du serveur.\n");
        exit(1);
    }
    abortRequest(500, 'Une erreur est survenue. Veuillez réessayer plus tard.');
});
if (PHP_SAPI !== 'cli') {
    startSecureSession();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header('Cache-Control: no-store');
    // HTML scalar fields must never accept arrays. Only these checkbox/order lists are arrays.
    foreach ([$_GET, $_POST] as $input) {
        foreach ($input as $name => $value) {
            if (is_array($value) && (!in_array($name, ['allergenes', 'plats', 'ordre'], true)
                || count(array_filter($value, 'is_array')) > 0)) {
                abortRequest(400, 'Format de formulaire invalide.');
            }
        }
    }
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        abortRequest(403, 'Le formulaire a expiré ou est invalide. Rechargez la page avant de réessayer.');
    }
}
