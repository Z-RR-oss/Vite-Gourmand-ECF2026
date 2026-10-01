<?php
require_once __DIR__ . '/catalogue.php';
$filterError = '';
try {
    $filters = catalogueFilters($_GET);
} catch (InvalidArgumentException $exception) {
    $filters = catalogueFilters([]);
    $filterError = $exception->getMessage();
}
$menus = catalogueMenus($pdo, $filters);
$themes = $pdo->query("SELECT DISTINCT theme FROM menus WHERE actif = 1 AND theme <> '' ORDER BY theme")->fetchAll(PDO::FETCH_COLUMN);
$regimes = $pdo->query("SELECT DISTINCT regime FROM menus WHERE actif = 1 AND regime <> '' ORDER BY regime")->fetchAll(PDO::FETCH_COLUMN);
?>
<section class="catalogue-section" aria-labelledby="catalogue-heading">
    <div class="section-heading"><div><p class="eyebrow">À CHAQUE OCCASION, SA TABLE</p><h2 id="catalogue-heading">Le goût du choix.</h2></div><p>Petite tablée ou grande occasion,<br>trouvez le menu qui vous ressemble.</p></div>
    <form id="menu-filters" class="catalogue-filters" action="menus.php" method="get" role="search" aria-label="Recherche de menus">
        <div class="filter-search"><label for="search-menu">Rechercher un menu</label><input type="search" name="search" id="search-menu" placeholder="Une envie en particulier ?" value="<?= e($filters['search']) ?>" maxlength="160"></div>
        <div><label for="theme">L’occasion</label><select name="theme" id="theme"><option value="">Tous les thèmes</option><?php foreach ($themes as $theme): ?><option value="<?= e($theme) ?>" <?= $filters['theme'] === $theme ? 'selected' : '' ?>><?= e($theme) ?></option><?php endforeach; ?></select></div>
        <div><label for="regime">Le régime</label><select name="regime" id="regime"><option value="">Tous les régimes</option><?php foreach ($regimes as $regime): ?><option value="<?= e($regime) ?>" <?= $filters['regime'] === $regime ? 'selected' : '' ?>><?= e($regime) ?></option><?php endforeach; ?></select></div>
        <div><label for="personnes">Vos convives</label><input type="number" name="personnes" id="personnes" min="1" max="10000" placeholder="Nombre" value="<?= e($filters['personnes']) ?>"></div>
        <div><label for="prix-min">Budget minimum (€)</label><input type="number" name="prix_min" id="prix-min" min="0" max="1000000" step="0.01" placeholder="0" value="<?= e($filters['prix_min']) ?>"></div>
        <div><label for="prix-max">Budget maximum (€)</label><input type="number" name="prix_max" id="prix-max" min="0" max="1000000" step="0.01" placeholder="Sans limite" value="<?= e($filters['prix_max']) ?>"></div>
        <div class="filter-actions"><button type="submit" class="button-small">Filtrer</button><button type="reset" class="button-secondary button-small">Réinitialiser</button></div>
        <p class="filter-help">Les prix correspondent au forfait pour le nombre minimum de personnes. « Vos convives » affiche les menus adaptés à la taille de votre groupe.</p>
    </form>
    <p id="menu-results-status" class="results-status" role="status" aria-live="polite"><?= $filterError !== '' ? e($filterError) : count($menus) . ' menu(s) à découvrir' ?></p>
    <div id="menus" class="menu-grid" aria-describedby="menu-results-status">
        <?php if (!$menus): ?><p class="empty-state">Aucun menu ne correspond à votre recherche. Essayez d’autres filtres.</p><?php endif; ?>
        <?php foreach ($menus as $menu): renderMenuCard($menu); endforeach; ?>
    </div>
</section>
