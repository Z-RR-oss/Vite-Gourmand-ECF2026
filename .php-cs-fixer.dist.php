<?php

// Les secrets locaux, les dépendances et les fichiers générés ne sont jamais formatés.
$finder = PhpCsFixer\Finder::create()
    ->in(['Config', 'Controllers', 'Public', 'Repositories', 'Services', 'Templates', 'Scripts', 'tests'])
    ->name('*.php')
    ->notName('*.local.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setCacheFile(__DIR__ . '/var/tools/php-cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'single_quote' => true,
        'no_unused_imports' => true,
        'no_extra_blank_lines' => ['tokens' => [
            'extra', 'curly_brace_block', 'parenthesis_brace_block',
            'square_brace_block', 'throw', 'return', 'use',
        ]],
        'no_whitespace_in_blank_line' => true,
        'whitespace_after_comma_in_array' => true,
        'trim_array_spaces' => true,
        'concat_space' => ['spacing' => 'one'],
    ])
    ->setFinder($finder);
