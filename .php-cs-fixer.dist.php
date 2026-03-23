<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    ->name('*.php')
    ->notName('*.tpl.php');

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony'                       => true,
        '@Symfony:risky'                 => true,
        'declare_strict_types'           => true,
        'native_function_invocation'     => ['include' => ['@all']],
        'global_namespace_import'        => ['import_classes' => false, 'import_functions' => false],
        'ordered_imports'                => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'              => true,
        'strict_param'                   => true,
        'array_syntax'                   => ['syntax' => 'short'],
        'concat_space'                   => ['spacing' => 'one'],
        'binary_operator_spaces'         => ['default' => 'align_single_space_minimal'],
        'yoda_style'                     => false,
        'phpdoc_align'                   => ['align' => 'vertical'],
        'trailing_comma_in_multiline'    => ['elements' => ['arrays', 'arguments', 'parameters']],
    ])
    ->setFinder($finder);
