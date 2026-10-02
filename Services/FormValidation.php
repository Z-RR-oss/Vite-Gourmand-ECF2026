<?php

declare(strict_types=1);

/** Associe chaque erreur à son champ pour le résumé et les technologies d'assistance. */
final class FormValidationException extends DomainException
{
    /** @param array<string, string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }
}

/** Conserve une saisie affichable sans convertir silencieusement un tableau en texte. */
function inputText(array $input, string $field, string $default = ''): string
{
    $value = $input[$field] ?? $default;
    return is_string($value) || is_numeric($value) ? trim((string) $value) : '';
}

/** @return array<string, string> */
function textFieldErrors(array $input, array $fields): array
{
    $errors = [];
    foreach ($fields as $field => [$label, $maximum, $required]) {
        $value = $input[$field] ?? '';
        if (!is_scalar($value) || ($required && trim((string) $value) === '')) {
            $errors[$field] = $label . ' : renseignez ce champ.';
        } elseif (mb_strlen(trim((string) $value)) > $maximum) {
            $errors[$field] = $label . ' : ' . $maximum . ' caractères maximum.';
        }
    }
    return $errors;
}
