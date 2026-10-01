<?php

/** Même règle d'accessibilité à l'ajout et à la modification d'une image. */
function validateMenuImageAlt(string $value): string
{
    $alt = trim($value);
    if ($alt === '' || mb_strlen($alt) > 255) {
        throw new DomainException('Décrivez l’image en 1 à 255 caractères.');
    }
    return $alt;
}

/**
 * Réencode les images téléversées : le fichier original n'est jamais publié.
 *
 * @param array $file Une entrée de $_FILES, jamais un chemin fourni librement.
 * @return string Chemin relatif à Public/, utilisable dans la galerie.
 * @throws DomainException Si le contenu ou les dimensions sont refusés.
 */
function storeMenuImage(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
        throw new DomainException('Sélectionnez une image JPEG, PNG ou WebP valide.');
    }
    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new DomainException('L’image doit peser au maximum 5 Mo.');
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
        || $info[0] * $info[1] > 16000000) {
        throw new DomainException('Image non reconnue ou supérieure à 16 millions de pixels.');
    }
    $source = @imagecreatefromstring(file_get_contents($file['tmp_name']));
    if (!$source) {
        throw new DomainException('Impossible de lire cette image.');
    }
    // Le nom d'origine n'est pas réutilisé : éviter collisions et chemins contrôlés par le client.
    $path = 'assets/uploads/' . bin2hex(random_bytes(20)) . '.png';
    $destination = __DIR__ . '/../Public/' . $path;
    try {
        imagesavealpha($source, true);
        if (!imagepng($source, $destination, 6)) {
            throw new RuntimeException('Stockage image indisponible.');
        }
    } finally {
        imagedestroy($source);
    }
    return $path;
}
