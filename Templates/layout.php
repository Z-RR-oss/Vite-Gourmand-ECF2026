<?php
/** Mise en page commune : navigation adaptée au rôle, contenu et horaires publics. */
require_once __DIR__ . '/../Config/security.php';

function renderHeader(string $title): void
{
    $role = $_SESSION['role'] ?? '';
    $staff = in_array($role, ['admin', 'employe'], true);
    $links = $staff
        ? ['admin-commandes.php' => 'Commandes', 'admin-menus.php' => 'Menus', 'admin-plats.php' => 'Plats', 'admin-horaires.php' => 'Horaires', 'admin-avis.php' => 'Avis']
        : ['index.php' => 'Accueil', 'menus.php' => 'Nos menus'];
    if ($role === 'admin') {
        $links = ['admin-statistiques.php' => 'Statistiques'] + $links + ['admin-employes.php' => 'Équipe'];
    } elseif ($role === 'utilisateur') {
        $links += ['mes-commandes.php' => 'Mes commandes', 'mon-profil.php' => 'Mon profil'];
    }
    if (!$staff) {
        $links['contact.php'] = 'Contact';
    }
    $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Vite & Gourmand, votre table à partager à Bordeaux. Découvrez nos menus et organisez votre prochain moment gourmand.">
    <meta name="theme-color" content="#672f3e">
    <title><?= e($title) ?> — Vite & Gourmand</title>
    <link rel="icon" href="assets/images/embleme.svg" type="image/svg+xml">
    <link rel="stylesheet" href="style.css?v=3">
    <script src="scripts.js?v=3" defer></script>
</head>
<body class="<?= $staff ? 'staff-page' : 'public-page' ?>">
<a class="skip-link" href="#main-content">Aller au contenu</a>
<div class="announcement"><span>Bordeaux & ses alentours</span><span>Des moments à partager, tout simplement.</span></div>
<header class="site-header">
    <a class="brand" href="index.php" aria-label="Vite et Gourmand, accueil">
        <img src="assets/images/embleme.svg" alt="" width="45" height="45">
        <span>Vite <i>&</i> Gourmand<small>LA TABLE À PARTAGER</small></span>
    </a>
    <button class="burger-menu" type="button" aria-expanded="false" aria-controls="main-navigation">Menu <span aria-hidden="true">☰</span></button>
    <nav id="main-navigation" class="navbar-links" aria-label="Navigation principale">
        <?php foreach ($links as $href => $label): ?>
            <a href="<?= e($href) ?>" <?= $current === $href ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach; ?>
        <?php if (isset($_SESSION['user_id'])): ?>
            <form method="post" action="logout.php" class="logout-form"><?= csrfInput() ?><button class="nav-account" type="submit">Déconnexion</button></form>
        <?php else: ?>
            <a class="nav-account" href="login.php" <?= $current === 'login.php' ? 'aria-current="page"' : '' ?>>Mon espace <span aria-hidden="true">↗</span></a>
        <?php endif; ?>
    </nav>
</header>
<main id="main-content" class="page-shell">
<?php
}

function renderFooter(PDO $pdo): void
{
    $rows = $pdo->query('SELECT jour, heure_ouverture, heure_fermeture, ferme FROM horaires')->fetchAll(PDO::FETCH_ASSOC);
    $horaires = [];
    foreach ($rows as $row) {
        $horaires[strtolower($row['jour'])] = $row;
    }
    ?>
</main>
<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-intro">
            <a class="footer-brand" href="index.php">Vite <i>&</i> Gourmand</a>
            <p>Le goût des bonnes choses.<br>Le plaisir d’être ensemble.</p>
            <p class="footer-location">Traiteur · Bordeaux</p>
            <a href="contact.php" class="text-link">Parlons de votre événement <span aria-hidden="true">↗</span></a>
        </div>
        <div>
            <h2>À votre service</h2>
            <ul class="footer-links"><li><a href="menus.php">Découvrir les menus</a></li><li><a href="contact.php">Nous contacter</a></li><li><a href="mentions-legales.php">Mentions légales</a></li><li><a href="cgv.php">Conditions générales de vente</a></li></ul>
        </div>
        <div>
            <h2>Nos horaires</h2>
            <dl class="opening-hours">
                <?php foreach (['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'] as $jour): $h = $horaires[strtolower($jour)] ?? null; ?>
                    <div><dt><?= $jour ?></dt><dd><?php if (!$h): ?>Non renseigné<?php elseif ((int) $h['ferme'] === 1): ?>Fermé<?php else: ?><?= e(substr((string) $h['heure_ouverture'], 0, 5)) ?> – <?= e(substr((string) $h['heure_fermeture'], 0, 5)) ?><?php endif; ?></dd></div>
                <?php endforeach; ?>
            </dl>
        </div>
    </div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> Vite & Gourmand</span><span>Projet pédagogique ECF · Service de démonstration</span><a href="#main-content">Retour en haut ↑</a></div>
</footer>
</body>
</html>
<?php
}
