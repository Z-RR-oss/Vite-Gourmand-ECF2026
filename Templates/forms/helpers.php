<?php

/** Les erreurs sont textuelles, liées aux champs et accessibles sans JavaScript. */
function renderFormErrors(array $errors): void
{
    if (!$errors) {
        return;
    }
    ?>
    <div class="erreur form-errors" role="alert" tabindex="-1" aria-labelledby="form-errors-title">
        <h2 id="form-errors-title">Vérifiez les informations saisies</h2>
        <ul>
            <?php foreach ($errors as $field => $message): ?>
                <li><a href="#<?= e($field) ?>"><?= e($message) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

function fieldErrorAttributes(array $errors, string $field): string
{
    return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . e($field) . '-error"' : '';
}

function renderFieldError(array $errors, string $field): void
{
    if (isset($errors[$field])) {
        echo '<p class="field-error" id="' . e($field) . '-error">' . e($errors[$field]) . '</p>';
    }
}
