<?php

/** Run daily using CLI. Never expose this file through the public document root. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../Config/database.php';
require_once __DIR__ . '/../Config/mail.php';
require_once __DIR__ . '/../Services/OrderRules.php';

// A database advisory lock also serializes cron runs on different application servers.
$lock = $pdo->query("SELECT GET_LOCK('vite_gourmand_equipment_reminders', 0)")->fetchColumn();
if ((int) $lock !== 1) {
    fwrite(STDERR, "Une vérification est déjà en cours.\n");
    exit(0);
}
$failed = false;
try {
    $ids = $pdo->query("SELECT id FROM commandes WHERE statut = 'en attente du retour de matériel' AND materiel_retourne = 0 AND date_debut_attente_retour IS NOT NULL AND notification_retard_envoyee = 0")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT * FROM commandes WHERE id = ? FOR UPDATE');
            $stmt->execute([$id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order || $order['statut'] !== 'en attente du retour de matériel' || (int) $order['materiel_retourne']
                || (int) $order['notification_retard_envoyee'] || !$order['date_debut_attente_retour']
                || calculerJoursOuvres($order['date_debut_attente_retour'], date('Y-m-d H:i:s')) <= 10) {
                $pdo->commit();
                continue;
            }
            $stmt = $pdo->prepare('UPDATE commandes SET frais_retard_materiel = 600 WHERE id = ?');
            $stmt->execute([$id]);
            $stmt = $pdo->prepare('SELECT nom, prenom, email FROM users WHERE id = ?');
            $stmt->execute([$order['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            // Hold this row while sending: no return/cancellation can race the reminder.
            // SMTP and SQL cannot form one atomic transaction: a crash immediately after
            // SMTP acceptance may cause one retry. Failed sends remain eligible for retry.
            $sent = envoyerEmail(
                $user['email'],
                $user['prenom'] . ' ' . $user['nom'],
                'Retard de retour du matériel - 600 €',
                '<p>Bonjour ' . e($user['prenom']) . ',</p><p>Le délai de 10 jours ouvrés de votre commande n°' . (int) $id . ' est dépassé. Des frais de 600 € sont appliqués conformément aux CGV.</p><p><a href="' . e(applicationUrl() . '/contact.php') . '">Contactez Vite &amp; Gourmand</a> pour organiser le retour du matériel.</p>'
            );
            if ($sent) {
                $stmt = $pdo->prepare('UPDATE commandes SET notification_retard_envoyee = 1, date_notification_retard = NOW() WHERE id = ?');
                $stmt->execute([$id]);
            } else {
                $failed = true;
            }
            $pdo->commit();
            echo 'Commande n°' . (int) $id . ': ' . ($sent ? 'notification envoyée' : 'envoi à réessayer') . PHP_EOL;
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Vérification matériel échouée pour commande ' . (int) $id . ': ' . get_class($error));
            $failed = true;
        }
    }
} finally {
    $pdo->query("SELECT RELEASE_LOCK('vite_gourmand_equipment_reminders')");
}
echo "Vérification des retards terminée.\n";
exit($failed ? 1 : 0);
