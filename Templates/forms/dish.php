<section class="content-panel">
    <h1><?= e($title) ?></h1>
    <?php renderFormErrors($errors); ?>
    <form method="post">
        <?= csrfInput() ?>
        <label for="nom">Nom du plat <span class="required-label">(obligatoire)</span></label>
        <input id="nom" name="nom" type="text" maxlength="255" value="<?= e($dish['nom'] ?? '') ?>" required<?= fieldErrorAttributes($errors, 'nom') ?>>
        <?php renderFieldError($errors, 'nom'); ?>
        <label for="description">Description</label>
        <textarea id="description" name="description" maxlength="10000"<?= fieldErrorAttributes($errors, 'description') ?>><?= e($dish['description'] ?? '') ?></textarea>
        <?php renderFieldError($errors, 'description'); ?>
        <label for="type_plat">Type de plat <span class="required-label">(obligatoire)</span></label>
        <select id="type_plat" name="type_plat" required<?= fieldErrorAttributes($errors, 'type_plat') ?>>
            <option value="">Choisir un type</option>
            <?php foreach (['entree' => 'Entrée', 'plat' => 'Plat', 'dessert' => 'Dessert'] as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= ($dish['type_plat'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
        <?php renderFieldError($errors, 'type_plat'); ?>
        <fieldset id="allergenes" tabindex="-1"<?= fieldErrorAttributes($errors, 'allergenes') ?>>
            <legend>Allergènes existants</legend>
            <?php renderFieldError($errors, 'allergenes'); ?>
            <?php if (!$allergens): ?><p>Aucun allergène enregistré.</p><?php endif; ?>
            <div class="allergenes">
                <?php foreach ($allergens as $allergen): ?>
                    <div class="allergene">
                        <input type="checkbox" id="allergene-<?= (int) $allergen['id'] ?>" name="allergenes[]" value="<?= (int) $allergen['id'] ?>"<?= in_array((string) $allergen['id'], $dish['allergenes'], true) ? ' checked' : '' ?>>
                        <label for="allergene-<?= (int) $allergen['id'] ?>"><?= e($allergen['nom']) ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <label for="nouveaux_allergenes">Ajouter de nouveaux allergènes</label>
        <p id="allergen-help" class="small-note">Séparez les noms par une virgule, par exemple : Lait, Œufs. Maximum 100 caractères par nom.</p>
        <input type="text" id="nouveaux_allergenes" name="nouveaux_allergenes" maxlength="2000" value="<?= e($dish['nouveaux_allergenes'] ?? '') ?>" aria-describedby="allergen-help<?= isset($errors['nouveaux_allergenes']) ? ' nouveaux_allergenes-error' : '' ?>"<?= isset($errors['nouveaux_allergenes']) ? ' aria-invalid="true"' : '' ?>>
        <?php renderFieldError($errors, 'nouveaux_allergenes'); ?>
        <button type="submit"><?= $editing ? 'Enregistrer les modifications' : 'Ajouter le plat' ?></button>
    </form>
    <a class="retour" href="admin-plats.php">Retour à la gestion des plats</a>
</section>
