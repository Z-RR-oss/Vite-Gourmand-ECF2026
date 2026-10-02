<?php

declare(strict_types=1);

require_once __DIR__ . '/FormValidation.php';

/** Valide avant toute conversion : « 4abc » ne doit jamais devenir quatre convives. */
function validateMenuInput(array $input): array
{
    $fields = [
        'titre' => ['Titre', 255, true],
        'description' => ['Description', 10000, true],
        'theme' => ['Thème', 100, true],
        'regime' => ['Régime', 100, true],
        'conditions_menu' => ['Conditions', 10000, false],
    ];
    $errors = textFieldErrors($input, $fields);
    $data = [];
    foreach ($fields as $field => $_) {
        $data[$field] = inputText($input, $field);
    }
    // Éviter l'arrondi implicite en base d'un tarif saisi avec trois décimales.
    $price = inputText($input, 'prix');
    if (!preg_match('/^\d{1,7}(?:\.\d{1,2})?$/D', $price)
        || (float) $price < 0.01 || (float) $price > 1000000) {
        $errors['prix'] = 'Prix : de 0,01 à 1 000 000 €, avec deux décimales maximum.';
    }
    $data['prix'] = $price;
    foreach ([
        'nb_personnes_min' => ['Nombre de personnes', 1, 10000],
        'stock_disponible' => ['Stock', 0, 100000],
        'delai_commande_heures' => ['Délai en heures', 0, 8760],
    ] as $field => [$label, $minimum, $maximum]) {
        $value = filter_var(
            $input[$field] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => $minimum, 'max_range' => $maximum]]
        );
        if ($value === false) {
            $errors[$field] = $label . ' : entier compris entre ' . $minimum . ' et ' . $maximum . '.';
        }
        $data[$field] = $value;
    }
    if ($errors) {
        throw new FormValidationException($errors);
    }
    $data['actif'] = isset($input['actif']) ? 1 : 0;
    return $data;
}

/** Les identifiants proposés doivent appartenir aux allergènes réellement lus en base. */
function validateDishInput(array $input, array $available): array
{
    $errors = textFieldErrors($input, [
        'nom' => ['Nom du plat', 255, true],
        'description' => ['Description', 10000, false],
        'nouveaux_allergenes' => ['Nouveaux allergènes', 2000, false],
    ]);
    $type = inputText($input, 'type_plat');
    if (!in_array($type, ['entree', 'plat', 'dessert'], true)) {
        $errors['type_plat'] = 'Type de plat : choisissez entrée, plat ou dessert.';
    }
    $selected = $input['allergenes'] ?? [];
    $ids = [];
    $allowed = array_map('intval', array_column($available, 'id'));
    if (!is_array($selected) || count($selected) > 1000) {
        $errors['allergenes'] = 'Sélectionnez des allergènes existants.';
    } else {
        foreach ($selected as $value) {
            $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($id === false || !in_array($id, $allowed, true)) {
                $errors['allergenes'] = 'Sélectionnez des allergènes existants.';
            } else {
                $ids[] = $id;
            }
        }
    }
    $names = [];
    foreach (explode(',', inputText($input, 'nouveaux_allergenes')) as $name) {
        $name = trim($name);
        if (mb_strlen($name) > 100) {
            $errors['nouveaux_allergenes'] = 'Chaque allergène est limité à 100 caractères.';
        } elseif ($name !== '') {
            $names[] = $name;
        }
    }
    if ($errors) {
        throw new FormValidationException($errors);
    }
    return ['nom' => inputText($input, 'nom'), 'description' => inputText($input, 'description'),
        'type_plat' => $type, 'allergenes' => array_values(array_unique($ids)),
        'nouveaux_allergenes' => array_values(array_unique($names))];
}
