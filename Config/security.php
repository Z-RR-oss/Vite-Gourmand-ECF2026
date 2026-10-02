<?php

/** Échappement à la sortie HTML : les données restent intactes en base. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function startSecureSession(): void
{
    if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('vite_gourmand_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(mixed $token): bool
{
    return is_string($token) && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfInput(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(generateCsrfToken()) . '">';
}

function abortRequest(int $status, string $message): never
{
    http_response_code($status);
    header('Content-Type: text/html; charset=UTF-8');
    require __DIR__ . '/../Templates/error.php';
    exit;
}

function requirePost(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        abortRequest(405, 'Cette action nécessite un formulaire de confirmation.');
    }
}

/** GET affiche le formulaire ; POST le traite. Les autres méthodes ne mutent rien. */
function requireFormMethod(): void
{
    if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        abortRequest(405, 'Cette méthode n’est pas autorisée pour ce formulaire.');
    }
}

function positiveId(mixed $value): int
{
    if (!is_scalar($value) || !preg_match('/^[1-9][0-9]*$/D', (string) $value)
        || filter_var($value, FILTER_VALIDATE_INT) === false) {
        abortRequest(400, 'Identifiant invalide.');
    }
    return (int) $value;
}

function passwordValidationError(string $password): ?string
{
    if (strlen($password) < 10 || strlen($password) > 72 || !preg_match('/[A-Z]/', $password)
        || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)
        || !preg_match('/[^a-zA-Z0-9\s]/', $password)) {
        return 'Le mot de passe doit contenir 10 à 72 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.';
    }
    return null;
}

function identityValidationError(array $data): ?string
{
    $labels = ['nom' => 'Nom', 'prenom' => 'Prénom', 'adresse' => 'Adresse', 'email' => 'Adresse email', 'gsm' => 'Téléphone'];
    foreach (['nom' => 50, 'prenom' => 50, 'adresse' => 255, 'email' => 100, 'gsm' => 20] as $key => $max) {
        if (!isset($data[$key]) || !is_string($data[$key]) || trim($data[$key]) === '') {
            return 'Le champ « ' . $labels[$key] . ' » est obligatoire.';
        }
        if (mb_strlen($data[$key]) > $max) {
            return 'Le champ « ' . $labels[$key] . ' » doit contenir au maximum ' . $max . ' caractères.';
        }
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return 'Saisissez une adresse email valide.';
    }
    if (!preg_match('/^[+0-9][0-9 .()\-]{6,19}$/D', $data['gsm'])) {
        return 'Saisissez un numéro de téléphone valide.';
    }
    return null;
}
