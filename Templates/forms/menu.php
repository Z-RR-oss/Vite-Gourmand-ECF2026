<section class="content-panel">
    <h1><?= e($title) ?></h1>
    <?php if ($editing): ?>
        <p><a class="button button-secondary" href="gerer-menu-images.php?id=<?= (int) $id ?>">Gérer les images de ce menu</a></p>
    <?php endif; ?>
    <?php renderFormErrors($errors); ?>
    <form method="post">
        <?= csrfInput() ?>
        <?php foreach ([
            'titre' => ['Titre', 'text', 255],
            'description' => ['Description', 'textarea', 10000],
            'prix' => ['Prix correspondant au minimum de personnes (€)', 'number', null],
            'nb_personnes_min' => ['Nombre minimum de personnes', 'number', null],
            'theme' => ['Thème', 'text', 100],
            'regime' => ['Régime alimentaire', 'text', 100],
            'stock_disponible' => ['Stock disponible', 'number', null],
            'conditions_menu' => ['Conditions particulières', 'textarea', 10000],
            'delai_commande_heures' => ['Délai minimum de commande en heures', 'number', null],
        ] as $field => [$label, $type, $maximum]): ?>
            <label for="<?= e($field) ?>"><?= e($label) ?><?php if ($field !== 'conditions_menu'): ?> <span class="required-label">(obligatoire)</span><?php endif; ?></label>
            <?php if ($type === 'textarea'): ?>
                <textarea id="<?= e($field) ?>" name="<?= e($field) ?>" maxlength="<?= $maximum ?>"<?= $field !== 'conditions_menu' ? ' required' : '' ?><?= fieldErrorAttributes($errors, $field) ?>><?= e($menu[$field] ?? '') ?></textarea>
            <?php else: ?>
                <input id="<?= e($field) ?>" name="<?= e($field) ?>" type="<?= e($type) ?>" value="<?= e($menu[$field] ?? '') ?>" required<?= fieldErrorAttributes($errors, $field) ?>
                    <?php if ($maximum !== null): ?>maxlength="<?= $maximum ?>"<?php endif; ?>
                    <?php if ($field === 'prix'): ?>min="0.01" max="1000000" step="0.01"<?php endif; ?>
                    <?php if ($field === 'nb_personnes_min'): ?>min="1" max="10000" step="1"<?php endif; ?>
                    <?php if ($field === 'stock_disponible'): ?>min="0" max="100000" step="1"<?php endif; ?>
                    <?php if ($field === 'delai_commande_heures'): ?>min="0" max="8760" step="1"<?php endif; ?>
                >
            <?php endif; ?>
            <?php renderFieldError($errors, $field); ?>
        <?php endforeach; ?>
        <div class="checkbox">
            <input type="checkbox" id="actif" name="actif"<?= (int) $menu['actif'] === 1 ? ' checked' : '' ?>>
            <label for="actif">Menu actif</label>
        </div>
        <button type="submit"><?= $editing ? 'Enregistrer les modifications' : 'Ajouter le menu' ?></button>
    </form>
    <a class="retour" href="admin-menus.php">Retour à la gestion des menus</a>
</section>
