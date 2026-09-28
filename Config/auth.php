<?php
function clearAuthentication(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/** Revalidate the database state on every request, including already open sessions. */
function refreshAuthentication(PDO $pdo): void
{
    if (PHP_SAPI === 'cli' || !isset($_SESSION['user_id'])) {
        return;
    }
    $stmt = $pdo->prepare('SELECT id, nom, prenom, email, role, actif, password FROM users WHERE id = ?');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || (int) $user['actif'] !== 1 || !in_array($user['role'], ['utilisateur', 'employe', 'admin'], true)
        || !isset($_SESSION['auth_version']) || !hash_equals($_SESSION['auth_version'], hash('sha256', $user['password']))) {
        clearAuthentication();
        return;
    }
    foreach (['nom', 'prenom', 'email', 'role'] as $key) {
        $_SESSION[$key] = $user[$key];
    }
}

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'commander.php' && ctype_digit((string) ($_GET['id'] ?? ''))) {
            $_SESSION['return_to'] = 'commander.php?id=' . (int) $_GET['id'];
        }
        header('Location: login.php', true, 303);
        exit;
    }
}

function requireRole(string|array $roles): void
{
    requireLogin();
    if (!in_array($_SESSION['role'] ?? '', (array) $roles, true)) {
        abortRequest(403, 'Vous ne disposez pas des droits nécessaires.');
    }
}

function requireAdminOrEmployee(): void
{
    requireRole(['admin', 'employe']);
}
