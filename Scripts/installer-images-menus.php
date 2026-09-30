<?php

/** Migration ciblée : conserver les photos ajoutées par l'équipe et les autres menus. */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../Config/database.php';

$apply = in_array('--apply', $argv, true);
$images = [
    ['Menu Vegan', 'assets/images/menu-vegan.svg', 'assets/images/menu-vegan-genere.jpg',
        'Présentation végétale : lentilles et légumes rôtis aux herbes'],
    ['Menu Noël', 'assets/images/menu-fete.svg', 'assets/images/menu-noel-genere.jpg',
        'Présentation de fête : volaille dorée, pommes de terre et légumes d’hiver'],
];

$pdo->beginTransaction();
try {
    foreach ($images as [$title, $oldPath, $path, $alt]) {
        if (!is_file(__DIR__ . '/../Public/' . $path)) {
            throw new RuntimeException('Déployer les images avant leur inscription en base.');
        }
        // La collation du schéma associe Noël et Noel ; aucun identifiant n'est présumé.
        $find = $pdo->prepare('SELECT id FROM menus WHERE titre = ? FOR UPDATE');
        $find->execute([$title]);
        foreach ($find->fetchAll(PDO::FETCH_COLUMN) as $menuId) {
            $gallery = $pdo->prepare('SELECT id, chemin_image FROM menu_images WHERE menu_id = ? ORDER BY id FOR UPDATE');
            $gallery->execute([$menuId]);
            $rows = $gallery->fetchAll();
            if (!$rows) {
                echo "$title : ajouter le visuel.\n";
                if ($apply) {
                    $insert = $pdo->prepare('INSERT INTO menu_images (menu_id, chemin_image, texte_alternatif) VALUES (?, ?, ?)');
                    $insert->execute([$menuId, $path, $alt]);
                }
            } else {
                foreach ($rows as $row) {
                    if ($row['chemin_image'] === $oldPath) {
                        echo "$title : remplacer l’illustration de démonstration.\n";
                        if ($apply) {
                            $update = $pdo->prepare('UPDATE menu_images SET chemin_image = ?, texte_alternatif = ? WHERE id = ?');
                            $update->execute([$path, $alt, $row['id']]);
                        }
                    }
                }
            }
        }
    }
    $apply ? $pdo->commit() : $pdo->rollBack();
    echo $apply ? "Images installées ; relancer est sans effet supplémentaire.\n" : "Plan uniquement. Ajouter --apply pour exécuter.\n";
} catch (Throwable $error) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $error;
}
