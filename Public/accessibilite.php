<?php
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Templates/layout.php';
renderHeader('Aide à l’accessibilité');
?>
<section class="legal-page">
    <h1>Aide à l’accessibilité</h1>
    <h2>Naviguer au clavier</h2>
    <p>Utilisez Tab pour passer au lien ou au champ suivant, et Maj + Tab pour revenir en arrière.
        Le premier lien « Aller au contenu » permet d’éviter la navigation répétée.</p>
    <p>Sur petit écran, le bouton « Menu » s’ouvre avec Entrée ou Espace. Échap le ferme et redonne le focus au bouton.</p>
    <p>Le <a href="plan-du-site.php">plan du site</a> propose un autre accès aux principales pages.</p>
    <h2>Lire et remplir les formulaires</h2>
    <p>Vous pouvez agrandir l’affichage avec les commandes de zoom de votre navigateur.
        Les champs obligatoires sont indiqués dans leur libellé. Les erreurs de saisie sont présentées en texte.</p>
    <p>Les valeurs des statistiques sont également proposées sous forme de tableau.
        Les zones de tableau qui défilent horizontalement sont accessibles au clavier.</p>
    <h2>Signaler une difficulté</h2>
    <p>Si vous rencontrez un problème, <a href="contact.php">contactez l’équipe</a> en précisant
        la page et l’action concernées. Ne transmettez pas votre mot de passe.</p>
    <p>Ce site est une démonstration pédagogique. Des contrôles d’accessibilité ont été réalisés,
        mais une évaluation exhaustive de conformité au RGAA n’a pas été achevée.
        Cette page d’aide ne constitue pas une déclaration de conformité.</p>
</section>
<?php renderFooter($pdo); ?>
