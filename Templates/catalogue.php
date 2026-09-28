<?php
/** Un chemin d’image du catalogue doit rester dans le répertoire public. */
function catalogueImage(?string $path): string
{
    if (!$path || str_contains($path, '\\') || str_contains($path, '..') || !preg_match('~^(?:assets/[a-zA-Z0-9_./-]+|[a-zA-Z0-9_-]+)\.(?:webp|png|jpg|jpeg|svg)$~i', $path)) {
        return 'assets/images/table-partage.svg';
    }
    $public = realpath(__DIR__ . '/../Public');
    $resolved = realpath($public . '/' . $path);
    return $resolved && str_starts_with($resolved, $public . DIRECTORY_SEPARATOR) ? $path : 'assets/images/table-partage.svg';
}

function catalogueFilters(array $input): array
{
    $filters = [];
    foreach (['search', 'theme', 'regime', 'prix_min', 'prix_max', 'personnes'] as $key) {
        if (isset($input[$key]) && !is_string($input[$key])) {
            throw new InvalidArgumentException('Un filtre contient une valeur invalide.');
        }
        $filters[$key] = trim($input[$key] ?? '');
        if (strlen($filters[$key]) > 160) {
            throw new InvalidArgumentException('Le texte recherché est trop long (160 caractères maximum).');
        }
    }
    foreach (['prix_min', 'prix_max'] as $key) {
        if ($filters[$key] !== '' && (!is_numeric($filters[$key]) || !is_finite((float) $filters[$key]) || (float) $filters[$key] < 0 || (float) $filters[$key] > 1000000)) {
            throw new InvalidArgumentException('Les prix doivent être compris entre 0 et 1 000 000 €.');
        }
    }
    if ($filters['personnes'] !== '' && filter_var($filters['personnes'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]) === false) {
        throw new InvalidArgumentException('Indiquez un nombre de personnes entre 1 et 10 000.');
    }
    if ($filters['prix_min'] !== '' && $filters['prix_max'] !== '' && (float) $filters['prix_min'] > (float) $filters['prix_max']) {
        throw new InvalidArgumentException('Le prix minimum doit être inférieur ou égal au prix maximum.');
    }
    return $filters;
}

function catalogueMenus(PDO $pdo, array $filters): array
{
    $conditions = ['m.actif = 1'];
    $params = [];
    foreach (['theme', 'regime'] as $key) {
        if ($filters[$key] !== '') {
            $conditions[] = "m.$key = :$key";
            $params[$key] = $filters[$key];
        }
    }
    foreach (['prix_min' => 'm.prix >=', 'prix_max' => 'm.prix <=', 'personnes' => 'm.nb_personnes_min <='] as $key => $sql) {
        if ($filters[$key] !== '') {
            $conditions[] = "$sql :$key";
            $params[$key] = $filters[$key];
        }
    }
    if ($filters['search'] !== '') {
        $conditions[] = 'm.titre LIKE :search';
        $params['search'] = '%' . $filters['search'] . '%';
    }
    $query = $pdo->prepare('SELECT m.id, m.titre, m.description, m.prix, m.nb_personnes_min, m.theme, m.regime, m.stock_disponible,
        (SELECT mi.chemin_image FROM menu_images mi WHERE mi.menu_id = m.id ORDER BY mi.id LIMIT 1) AS image
        FROM menus m WHERE ' . implode(' AND ', $conditions) . ' ORDER BY m.id');
    $query->execute($params);
    $menus = $query->fetchAll(PDO::FETCH_ASSOC);
    foreach ($menus as &$menu) {
        $menu['image'] = catalogueImage($menu['image']);
    }
    return $menus;
}

function renderMenuCard(array $menu): void
{
    ?>
<article class="menu-card">
    <a class="menu-image-link" href="menu.php?id=<?= (int) $menu['id'] ?>" tabindex="-1" aria-hidden="true"><img src="<?= e($menu['image']) ?>" alt="" width="600" height="420" loading="lazy"><span class="menu-theme"><?= e($menu['theme']) ?></span></a>
    <div class="menu-card-body">
        <div class="menu-card-meta"><span><?= e($menu['regime']) ?></span><span><?= (int) $menu['nb_personnes_min'] ?> pers. minimum</span></div>
        <h3><a href="menu.php?id=<?= (int) $menu['id'] ?>"><?= e($menu['titre']) ?></a></h3>
        <p><?= e($menu['description']) ?></p>
        <div class="menu-card-bottom"><div><strong><?= number_format((float) $menu['prix'], 2, ',', ' ') ?> €</strong><small>pour <?= (int) $menu['nb_personnes_min'] ?> personnes</small></div><a class="round-link" href="menu.php?id=<?= (int) $menu['id'] ?>" aria-label="Découvrir <?= e($menu['titre']) ?>">↗</a></div>
        <?php if ((int) $menu['stock_disponible'] <= 0): ?><span class="badge badge-muted">Momentanément indisponible</span><?php endif; ?>
    </div>
</article>
<?php
}
