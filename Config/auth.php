<?php

/** Conserve la session PHP, mais invalide l'identité et les jetons qui lui étaient liés. */
function clearAuthentication(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/** Révoque aussi les sessions déjà ouvertes après désactivation ou changement de mot de passe. */
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

/** Destination interne commune aux connexions et aux sessions déjà authentifiées. */
function authenticatedHomePath(string $role): string
{
    return in_array($role, ['admin', 'employe'], true)
        ? 'admin-commandes.php'
        : 'mes-commandes.php';
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

/** La visibilité d'un lien ne suffit pas : chaque contrôleur protégé appelle cette garde. */
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
