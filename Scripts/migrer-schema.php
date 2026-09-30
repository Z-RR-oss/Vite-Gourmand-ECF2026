<?php

/** Additive upgrade of the existing schema. Default is a read-only plan. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../Config/database.php';
$apply = in_array('--apply', $argv, true);
$changes = [];
$column = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
foreach (['notification_retard_envoyee' => 'TINYINT(1) NOT NULL DEFAULT 0', 'date_notification_retard' => 'DATETIME DEFAULT NULL'] as $name => $definition) {
    $column->execute(['commandes', $name]);
    if (!(int) $column->fetchColumn()) {
        $changes[] = "ALTER TABLE commandes ADD COLUMN $name $definition";
    }
}
$table = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='password_reset_tokens'")->fetchColumn();
if (!(int) $table) {
    $changes[] = 'CREATE TABLE password_reset_tokens (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, used TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci';
}
$index = $pdo->prepare('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?');
foreach ([['commandes', 'idx_commandes_created_menu', 'CREATE INDEX idx_commandes_created_menu ON commandes(created_at,menu_id)'], ['password_reset_tokens', 'idx_reset_hash', 'CREATE UNIQUE INDEX idx_reset_hash ON password_reset_tokens(token_hash)'], ['password_reset_tokens', 'idx_reset_expiration', 'CREATE INDEX idx_reset_expiration ON password_reset_tokens(expires_at)']] as [$name,$key,$ddl]) {
    $index->execute([$name, $key]);
    if (!(int) $index->fetchColumn()) {
        $changes[] = $ddl;
    }
}
foreach ($changes as $sql) {
    echo ($apply ? 'Application : ' : 'Prévu : ') . $sql . ";\n";
    if ($apply) {
        $pdo->exec($sql);
    }
}
foreach (['commandes', 'historique_statuts'] as $name) {
    $number = (int) $pdo->query("SELECT COUNT(*) FROM $name WHERE statut='livrée'")->fetchColumn();
    if ($number > 0) {
        echo "$name : normaliser $number statut(s) historique(s) livrée vers livré.\n";
        if ($apply) {
            $pdo->exec("UPDATE $name SET statut='livré' WHERE statut='livrée'");
        }
    }
}
echo $apply ? "Migration terminée.\n" : "Plan uniquement. Sauvegarder puis ajouter --apply pour appliquer.\n";
