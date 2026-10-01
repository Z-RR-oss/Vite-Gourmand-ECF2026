<?php

/** Renvoie la première erreur affichable, ou null ; aucun accès SQL ni écriture ici. */
function menuValidationError(array $input): ?string
{
    foreach (['titre' => 255, 'description' => 10000, 'theme' => 100, 'regime' => 100] as $field => $max) {
        if (trim($input[$field] ?? '') === '' || mb_strlen($input[$field]) > $max) {
            return 'Vérifiez les champs du menu et leur longueur maximale.';
        }
    }
    if (mb_strlen($input['conditions_menu'] ?? '') > 10000) {
        return 'Les conditions sont limitées à 10 000 caractères.';
    }
    $price = filter_var($input['prix'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($price === false || !is_finite($price) || $price <= 0 || $price > 1000000) {
        return 'Le prix doit être compris entre 0,01 et 1 000 000 €.';
    }
    foreach (['nb_personnes_min' => [1, 10000], 'stock_disponible' => [0, 100000], 'delai_commande_heures' => [0, 8760]] as $key => $limits) {
        if (filter_var($input[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => $limits[0], 'max_range' => $limits[1]]]) === false) {
            return 'Indiquez des nombres entiers valides pour les personnes, le stock et le délai (maximum un an).';
        }
    }
    return null;
}
/** Les identifiants proposés doivent appartenir aux allergènes réellement lus en base. */
function dishValidationError(array $input, array $available): ?string
{
    if (mb_strlen(trim($input['nom'] ?? '')) > 255 || mb_strlen($input['description'] ?? '') > 10000) {
        return 'Le nom est limité à 255 caractères et la description à 10 000.';
    }
    $selected = $input['allergenes'] ?? [];
    if (!is_array($selected) || array_diff($selected, array_column($available, 'id'))) {
        return 'Sélectionnez des allergènes existants.';
    }
    foreach (explode(',', $input['nouveaux_allergenes'] ?? '') as $name) {
        if (mb_strlen(trim($name)) > 100) {
            return 'Un allergène est limité à 100 caractères.';
        }
    }
    return null;
}
