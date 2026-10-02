<?php

declare(strict_types=1);

/** Calendrier de Bordeaux : jours fériés nationaux de France métropolitaine. */
function metropolitanHolidays(int $year): array
{
    static $cache = [];
    if (isset($cache[$year])) {
        return $cache[$year];
    }
    // Calcul grégorien de Pâques (Meeus/Jones/Butcher), indépendant du fuseau système.
    $a = $year % 19;
    $b = intdiv($year, 100);
    $c = $year % 100;
    $d = intdiv($b, 4);
    $e = $b % 4;
    $f = intdiv($b + 8, 25);
    $g = intdiv($b - $f + 1, 3);
    $h = (19 * $a + $b - $d - $g + 15) % 30;
    $i = intdiv($c, 4);
    $k = $c % 4;
    $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
    $m = intdiv($a + 11 * $h + 22 * $l, 451);
    $month = intdiv($h + $l - 7 * $m + 114, 31);
    $day = ($h + $l - 7 * $m + 114) % 31 + 1;
    $easter = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day), new DateTimeZone('Europe/Paris'));
    $dates = [];
    foreach (['01-01', '05-01', '05-08', '07-14', '08-15', '11-01', '11-11', '12-25'] as $date) {
        $dates[] = sprintf('%04d-%s', $year, $date);
    }
    foreach ([1, 39, 50] as $offset) {
        $dates[] = $easter->modify('+' . $offset . ' days')->format('Y-m-d');
    }
    sort($dates);
    return $cache[$year] = array_values(array_unique($dates));
}
