<?php
/** The menu price covers nb_personnes_min guests; discount excludes delivery. */
function creerDatePrestation(string $date, string $heure): ?DateTimeImmutable
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) || !preg_match('/^\d{2}:\d{2}$/D', $heure)) {
        return null;
    }
    $value = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $heure);
    $errors = DateTimeImmutable::getLastErrors();
    return $value !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) ? $value : null;
}

function verifierDelaiCommande(string $date, string $heure, int $delaiHeures): ?string
{
    $value = creerDatePrestation($date, $heure);
    if (!$value) {
        return 'La date ou l’heure de prestation est invalide.';
    }
    if ($value < (new DateTimeImmutable())->modify('+' . max(0, $delaiHeures) . ' hours')) {
        return 'Ce menu doit être commandé au moins ' . max(0, $delaiHeures) . ' heure(s) avant la prestation.';
    }
    return null;
}

function calculerPrixCommande(array $menu, int $nbPersonnes, float $distanceKm): array
{
    $minimum = (int) $menu['nb_personnes_min'];
    if ($minimum < 1 || $nbPersonnes < $minimum || $nbPersonnes > 10000 || !is_finite($distanceKm)
        || $distanceKm < 0 || $distanceKm > 10000 || (float) $menu['prix'] <= 0) {
        throw new DomainException('Le nombre de personnes, la distance ou le prix du menu est invalide.');
    }
    $repas = round((float) $menu['prix'] * 100 * $nbPersonnes / $minimum);
    $pourcentage = $nbPersonnes >= $minimum + 5 ? 10 : 0;
    $remise = round($repas * $pourcentage / 100);
    $livraison = $distanceKm > 0 ? round(500 + 59 * $distanceKm) : 0;
    return ['prix_repas' => $repas / 100, 'remise_pourcentage' => $pourcentage,
        'montant_remise' => $remise / 100, 'frais_livraison' => $livraison / 100,
        'prix_total' => ($repas - $remise + $livraison) / 100];
}

function validateOrderInput(array $input, array $menu): array
{
    $data = [];
    foreach (['adresse_prestation' => 255, 'lieu_prestation' => 255, 'date_prestation' => 10, 'heure_prestation' => 5] as $key => $max) {
        $value = $input[$key] ?? '';
        if (!is_string($value) || trim($value) === '' || mb_strlen($value) > $max) {
            throw new DomainException('Renseignez une adresse, un lieu, une date et une heure valides.');
        }
        $data[$key] = trim($value);
    }
    $people = filter_var($input['nb_personnes'] ?? null, FILTER_VALIDATE_INT);
    $distance = filter_var($input['distance_km'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($people === false || $distance === false || $people < (int) $menu['nb_personnes_min']) {
        throw new DomainException('Le nombre de personnes doit être au minimum de ' . (int) $menu['nb_personnes_min'] . ' et la distance doit être un nombre positif ou nul.');
    }
    $error = verifierDelaiCommande($data['date_prestation'], $data['heure_prestation'], (int) $menu['delai_commande_heures']);
    if ($error !== null) {
        throw new DomainException($error);
    }
    return $data + ['nb_personnes' => $people, 'distance_km' => $distance] + calculerPrixCommande($menu, $people, $distance);
}

function allowedOrderTransitions(string $status): array
{
    return match ($status) {
        'en attente' => ['accepté'],
        'accepté' => ['en préparation'],
        'en préparation' => ['en cours de livraison'],
        'en cours de livraison' => ['livré'],
        'livré' => ['en attente du retour de matériel', 'terminée'],
        // Finishing a loan is only possible through returnEquipment().
        default => [],
    };
}

/** Monday–Friday, excluding the start day. Public holidays are not deducted. */
function calculerJoursOuvres(string $dateDebut, string $dateFin): int
{
    $day = (new DateTimeImmutable($dateDebut))->setTime(0, 0)->modify('+1 day');
    $end = (new DateTimeImmutable($dateFin))->setTime(0, 0);
    $count = 0;
    while ($day <= $end) {
        if ((int) $day->format('N') <= 5) {
            $count++;
        }
        $day = $day->modify('+1 day');
    }
    return $count;
}
