<?php

declare(strict_types=1);

require_once __DIR__ . '/../Repositories/StatisticsRepository.php';

/** Prépare des agrégats sans données clients ; le tableau de bord lit uniquement Firebase. */
final class StatisticsService
{
    public function __construct(private StatisticsRepository $repository)
    {
    }

    public function synchronize(PDO $pdo): array
    {
        // Lire catalogue et agrégats dans une même transaction, puis fermer SQL avant l'appel réseau.
        $pdo->beginTransaction();
        try {
            $menus = $pdo->query('SELECT id, titre FROM menus ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
            $rows = $pdo->query("SELECT DATE(created_at) AS day, menu_id,
                SUM(CASE WHEN statut <> 'annulée' THEN 1 ELSE 0 END) AS commandes,
                SUM(CASE WHEN statut = 'annulée' THEN 1 ELSE 0 END) AS annulees,
                SUM(CASE WHEN statut <> 'annulée' THEN nb_personnes ELSE 0 END) AS personnes,
                SUM(CASE WHEN statut IN ('livré', 'livrée', 'en attente du retour de matériel', 'terminée')
                    THEN CAST(ROUND(prix_total * 100) AS SIGNED) ELSE 0 END) AS ca_centimes
                FROM commandes GROUP BY DATE(created_at), menu_id ORDER BY day, menu_id")->fetchAll(PDO::FETCH_ASSOC);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
        $snapshot = self::buildSnapshot($menus, $rows, gmdate('c'));
        $this->repository->publish($snapshot);
        return $snapshot;
    }

    /** Normalisation pure des agrégats SQL, testable sans réseau ni base de données. */
    public static function buildSnapshot(array $menus, array $rows, string $syncedAt): array
    {
        $snapshot = ['schema_version' => 1, 'synced_at' => $syncedAt,
            'date_basis' => 'created_at', 'currency' => 'EUR', 'menus' => [], 'days' => []];
        foreach ($menus as $menu) {
            $id = (int) $menu['id'];
            $snapshot['menus']['menu_' . $id] = ['id' => $id, 'titre' => (string) $menu['titre']];
        }
        foreach ($rows as $row) {
            $snapshot['days'][$row['day']]['menu_' . (int) $row['menu_id']] = [
                'commandes' => (int) $row['commandes'],
                'annulees' => (int) $row['annulees'],
                'personnes' => (int) $row['personnes'],
                'ca_centimes' => (int) $row['ca_centimes'],
            ];
        }
        return $snapshot;
    }

    /** Les libellés et valeurs affichés proviennent tous du même instantané Firebase. */
    public function dashboard(array $filters): array
    {
        return self::summarize($this->repository->read(), $filters);
    }

    /**
     * Normalise la période en dates inclusives, dans le fuseau métier.
     *
     * @return array{periode: string, menu: ?int, du: string, au: string}
     */
    public static function parseFilters(array $query, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today', new DateTimeZone('Europe/Paris'));
        $period = $query['periode'] ?? '30';
        if (!is_string($period) || !in_array($period, ['7', '30', '90', '365', 'tout', 'personnalisee'], true)) {
            throw new InvalidArgumentException('Choisissez une période proposée.');
        }
        $rawMenu = $query['menu'] ?? '';
        if (!is_string($rawMenu) || ($rawMenu !== '' && !preg_match('/^[1-9][0-9]{0,9}$/D', $rawMenu))) {
            throw new InvalidArgumentException('Le menu sélectionné est invalide.');
        }
        $from = $to = '';
        if ($period === 'personnalisee') {
            $from = $query['du'] ?? '';
            $to = $query['au'] ?? '';
            if (!self::isDate($from) || !self::isDate($to)) {
                throw new InvalidArgumentException('Indiquez deux dates valides pour la période personnalisée.');
            }
            if ($from > $to) {
                throw new InvalidArgumentException('La date de début doit précéder la date de fin.');
            }
        } elseif ($period !== 'tout') {
            $from = $today->modify('-' . ((int) $period - 1) . ' days')->format('Y-m-d');
            $to = $today->format('Y-m-d');
        }
        return ['periode' => $period, 'menu' => $rawMenu === '' ? null : (int) $rawMenu, 'du' => $from, 'au' => $to];
    }

    public static function summarize(array $snapshot, array $filters): array
    {
        $menus = $snapshot['menus'] ?? [];
        if ($filters['menu'] !== null && !isset($menus['menu_' . $filters['menu']])) {
            throw new InvalidArgumentException('Ce menu n’existe pas dans les statistiques synchronisées.');
        }
        $rows = [];
        foreach ($menus as $key => $menu) {
            if (!is_array($menu) || !is_int($menu['id'] ?? null) || $key !== 'menu_' . $menu['id']
                || !is_string($menu['titre'] ?? null)) {
                throw new RuntimeException('Catalogue Firebase invalide.');
            }
            if ($filters['menu'] === null || $menu['id'] === $filters['menu']) {
                $rows[$key] = $menu + self::emptyTotals();
            }
        }
        foreach ($snapshot['days'] ?? [] as $day => $dayRows) {
            if (!self::isDate($day) || !is_array($dayRows)) {
                throw new RuntimeException('Agrégats journaliers Firebase invalides.');
            }
            if (($filters['du'] !== '' && $day < $filters['du']) || ($filters['au'] !== '' && $day > $filters['au'])) {
                continue;
            }
            foreach ($dayRows as $key => $values) {
                if (!isset($menus[$key])) {
                    throw new RuntimeException('Menu manquant dans les agrégats Firebase.');
                }
                if (!isset($rows[$key])) {
                    continue;
                }
                foreach (self::emptyTotals() as $metric => $unused) {
                    if (!is_int($values[$metric] ?? null) || $values[$metric] < 0) {
                        throw new RuntimeException('Valeurs statistiques Firebase invalides.');
                    }
                    $rows[$key][$metric] += $values[$metric];
                }
            }
        }
        // Départager par titre garantit un classement stable lorsque les volumes sont égaux.
        uasort($rows, static fn (array $a, array $b): int => $b['commandes'] <=> $a['commandes'] ?: strcasecmp($a['titre'], $b['titre']));
        $totals = self::emptyTotals();
        foreach ($rows as $row) {
            foreach ($totals as $metric => $value) {
                $totals[$metric] += $row[$metric];
            }
        }
        return ['synced_at' => $snapshot['synced_at'], 'menus' => array_values($menus), 'rows' => array_values($rows), 'totals' => $totals];
    }

    private static function emptyTotals(): array
    {
        return ['commandes' => 0, 'annulees' => 0, 'personnes' => 0, 'ca_centimes' => 0];
    }

    private static function isDate(mixed $value): bool
    {
        if (!is_string($value) || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D', $value)) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
