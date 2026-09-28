<?php
require_once __DIR__ . '/../Config/mail.php';

/** Email is a post-commit side effect: a delivery failure never undoes an order. */
function notifyOrderStatus(PDO $pdo, int $id, string $status): bool
{
    $stmt = $pdo->prepare('SELECT c.*, u.nom, u.prenom, u.email, m.titre FROM commandes c JOIN users u ON u.id = c.user_id JOIN menus m ON m.id = c.menu_id WHERE c.id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return false;
    }
    $baseUrl = rtrim(applicationUrl(), '/');
    $content = '<p>Bonjour ' . e($order['prenom']) . ',</p><p>Votre commande n°' . $id . ' pour le menu <strong>' . e($order['titre']) . '</strong> : </p>';
    if ($status === 'en attente') {
        $subject = 'Confirmation de votre commande';
        $content .= '<p>Votre commande est enregistrée pour le ' . e($order['date_prestation']) . ' à ' . e($order['heure_prestation']) . ', pour ' . (int) $order['nb_personnes'] . ' personnes.</p><p>Livraison : ' . e($order['adresse_prestation']) . ' — ' . e($order['lieu_prestation']) . '.</p><p>Total : ' . number_format((float) $order['prix_total'], 2, ',', ' ') . ' €.</p>';
    } elseif ($status === 'en attente du retour de matériel') {
        $subject = 'Retour du matériel : délai de 10 jours ouvrés';
        $content .= '<p>Le matériel prêté doit être rendu sous 10 jours ouvrés (du lundi au vendredi, hors jour de départ). Passé ce délai, des frais de 600 € sont appliqués conformément aux CGV.</p><p>Contactez notre entreprise pour organiser le retour : <a href="' . e($baseUrl . '/contact.php') . '">contacter Vite &amp; Gourmand</a>.</p>';
    } elseif ($status === 'terminée') {
        $subject = 'Donnez votre avis sur votre commande';
        $content .= '<p>Votre commande est terminée. Merci de votre confiance.</p><p><a href="' . e($baseUrl . '/laisser-avis.php?id=' . $id) . '">Laisser une note et un commentaire</a>.</p>';
    } else {
        return true;
    }
    try {
        return envoyerEmail($order['email'], $order['prenom'] . ' ' . $order['nom'], $subject, $content);
    } catch (Throwable $error) {
        error_log('Échec de notification de commande ' . $id . ': ' . $error->getMessage());
        return false;
    }
}
