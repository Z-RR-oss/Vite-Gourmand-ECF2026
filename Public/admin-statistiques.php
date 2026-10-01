<?php
declare(strict_types=1);

require_once __DIR__ . '/../Config/database.php';
requireRole('admin');
require_once __DIR__ . '/../Services/StatisticsService.php';
require_once __DIR__ . '/../Templates/layout.php';

$error = '';
$dashboard = null;
$filters = StatisticsService::parseFilters([]);
try {
    $filters = StatisticsService::parseFilters($_GET);
    $repository = new StatisticsRepository(require __DIR__ . '/../Config/nosql.php');
    $dashboard = (new StatisticsService($repository))->dashboard($filters);
} catch (InvalidArgumentException $exception) {
    http_response_code(400);
    $error = $exception->getMessage();
} catch (Throwable $exception) {
    http_response_code(503);
    error_log('Statistics dashboard unavailable [' . get_class($exception) . '].');
    $error = 'Les statistiques sont indisponibles. Vérifiez la configuration Firebase et lancez une synchronisation avant de réessayer.';
}

renderHeader('Statistiques');
?>
<div class="page-heading">
    <p class="eyebrow">L’activité de la maison</p>
    <h1>Chaque menu raconte une histoire.</h1>
    <p>Comparez les commandes et le chiffre d’affaires de vos menus.</p>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-error" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<section class="card" aria-labelledby="filters-title">
    <h2 id="filters-title">Votre période, vos menus</h2>
    <form class="filters" method="get" action="admin-statistiques.php">
        <div class="form-group">
            <label for="menu">Menu</label>
            <select id="menu" name="menu">
                <option value="">Tous les menus</option>
                <?php foreach ($dashboard['menus'] ?? [] as $menu): ?>
                    <option value="<?= (int) $menu['id'] ?>" <?= $filters['menu'] === $menu['id'] ? 'selected' : '' ?>><?= e($menu['titre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="periode">Période</label>
            <select id="periode" name="periode" aria-describedby="period-help">
                <?php foreach (['7' => '7 derniers jours', '30' => '30 derniers jours', '90' => '90 derniers jours', '365' => '365 derniers jours', 'tout' => 'Tout l’historique', 'personnalisee' => 'Dates personnalisées'] as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= $filters['periode'] === (string) $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="du">Du <span id="du-required" class="required-label" hidden>(obligatoire)</span></label>
            <input id="du" name="du" type="date" value="<?= e($filters['du']) ?>" aria-describedby="period-help">
        </div>
        <div class="form-group">
            <label for="au">Au inclus <span id="au-required" class="required-label" hidden>(obligatoire)</span></label>
            <input id="au" name="au" type="date" value="<?= e($filters['au']) ?>" aria-describedby="period-help">
        </div>
        <button type="submit" class="button">Afficher</button>
        <a class="button button-secondary" href="admin-statistiques.php">Réinitialiser</a>
    </form>
    <p id="period-help">Les dates libres s’appliquent avec « Dates personnalisées ». La période porte sur la date de création des commandes dans le fuseau de la base, réglé sur l’heure de Paris.</p>
</section>

<?php if ($dashboard !== null): ?>
    <p class="stats-update">Source : Firebase Realtime Database. Dernière synchronisation :
        <time datetime="<?= e($dashboard['synced_at']) ?>"><?= e($dashboard['synced_at']) ?></time>.
    </p>
    <div class="stats-grid">
        <div class="stat-card"><span>Commandes hors annulations</span><strong><?= number_format($dashboard['totals']['commandes'], 0, ',', ' ') ?></strong></div>
        <div class="stat-card"><span>Chiffre d’affaires livré</span><strong><?= number_format($dashboard['totals']['ca_centimes'] / 100, 2, ',', ' ') ?> €</strong></div>
        <div class="stat-card"><span>Convives hors annulations</span><strong><?= number_format($dashboard['totals']['personnes'], 0, ',', ' ') ?></strong></div>
        <div class="stat-card"><span>Commandes annulées</span><strong><?= number_format($dashboard['totals']['annulees'], 0, ',', ' ') ?></strong></div>
    </div>

    <section class="card" aria-labelledby="chart-title">
        <h2 id="chart-title">Les menus en perspective</h2>
        <p id="chart-summary" aria-live="polite">Comparaison du nombre de commandes hors annulations. Les valeurs exactes figurent dans le tableau ci-dessous.</p>
        <div class="form-group" id="chart-controls" hidden>
            <label for="chart-metric">Comparer selon</label>
            <select id="chart-metric"><option value="commandes">Nombre de commandes</option><option value="ca_centimes">Chiffre d’affaires livré</option></select>
        </div>
        <?php if ($dashboard['totals']['commandes'] === 0 && $dashboard['totals']['annulees'] === 0): ?>
            <p>Aucune commande sur cette période pour les menus sélectionnés.</p>
        <?php endif; ?>
        <?php $maximum = max(1, ...array_column($dashboard['rows'], 'commandes')); ?>
        <div class="chart-bars" aria-describedby="chart-summary">
            <?php foreach ($dashboard['rows'] as $row): ?>
                <div class="chart-row" data-commandes="<?= (int) $row['commandes'] ?>" data-ca_centimes="<?= (int) $row['ca_centimes'] ?>">
                    <span><?= e($row['titre']) ?></span>
                    <meter class="chart-bar" min="0" max="<?= $maximum ?>" value="<?= (int) $row['commandes'] ?>" aria-hidden="true"><?= (int) $row['commandes'] ?></meter>
                    <strong class="chart-value"><?= (int) $row['commandes'] ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="card" aria-labelledby="table-title">
        <h2 id="table-title">Le détail, à la carte</h2>
        <div class="table-wrap" tabindex="0" role="region" aria-label="Tableau des statistiques par menu, défilement horizontal possible">
            <table>
                <caption>Résultats par menu pour la période sélectionnée</caption>
                <thead><tr><th scope="col">Menu</th><th scope="col">Commandes hors annulations</th><th scope="col">Annulées</th><th scope="col">Convives hors annulations</th><th scope="col">CA livré</th></tr></thead>
                <tbody>
                    <?php foreach ($dashboard['rows'] as $row): ?>
                        <tr><th scope="row"><?= e($row['titre']) ?></th><td><?= (int) $row['commandes'] ?></td><td><?= (int) $row['annulees'] ?></td><td><?= (int) $row['personnes'] ?></td><td><?= number_format($row['ca_centimes'] / 100, 2, ',', ' ') ?> €</td></tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><th scope="row">Total</th><td><?= (int) $dashboard['totals']['commandes'] ?></td><td><?= (int) $dashboard['totals']['annulees'] ?></td><td><?= (int) $dashboard['totals']['personnes'] ?></td><td><?= number_format($dashboard['totals']['ca_centimes'] / 100, 2, ',', ' ') ?> €</td></tr></tfoot>
            </table>
        </div>
        <p>Le CA livré inclut les commandes livrées, en attente de retour de matériel et terminées : montant après remise, livraison comprise, pénalités de matériel exclues. Les commandes en attente, acceptées, en préparation, en livraison et annulées ne génèrent pas encore de CA livré.</p>
    </section>
<?php endif; ?>
<script src="assets/js/statistics.js" defer></script>
<?php renderFooter($pdo); ?>
