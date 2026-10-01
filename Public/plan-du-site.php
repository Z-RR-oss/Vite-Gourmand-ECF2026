<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Plan du site');
?>
<section class="legal-page">
    <h1>Plan du site</h1>
    <p>Retrouvez les principales pages de Vite &amp; Gourmand.</p>
    <h2>Découvrir notre maison</h2>
    <ul>
        <li><a href="index.php">Accueil, présentation et avis clients</a></li>
        <li><a href="menus.php">Tous les menus et filtres de recherche</a></li>
        <li><a href="contact.php">Contacter l’équipe</a></li>
    </ul>
    <h2>Votre espace</h2>
    <ul>
        <li><a href="login.php">Se connecter</a></li>
        <li><a href="register.php">Créer un compte client</a></li>
        <li><a href="mot-de-passe-oublie.php">Réinitialiser un mot de passe oublié</a></li>
    </ul>
    <p>Après connexion, le menu donne accès aux commandes et aux fonctions autorisées pour votre compte.</p>
    <h2>Informations pratiques</h2>
    <ul>
        <li><a href="mentions-legales.php">Mentions légales et données personnelles</a></li>
        <li><a href="cgv.php">Conditions générales de vente</a></li>
        <li><a href="accessibilite.php">Aide à l’accessibilité</a></li>
    </ul>
</section>
<?php renderFooter($pdo); ?>
