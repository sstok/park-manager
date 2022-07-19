<?php

$header = <<<EOF
This Source Code Form is subject to the terms of the Mozilla Public
License, v. 2.0. If a copy of the MPL was not distributed with this
file, You can obtain one at http://mozilla.org/MPL/2.0/.
EOF;

/** @var \Symfony\Component\Finder\Finder $finder */
$finder = PhpCsFixer\Finder::create();
$finder
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/bin',
        __DIR__ . '/public',
    ])
    ->notPath('config/routing');

$config = new PhpCsFixer\Config();
$config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PHP80Migration' => true,
        '@PHP80Migration:risky' => true,
        '@PHPUnit84Migration:risky' => true,
        '@DoctrineAnnotation' => true,
        'blank_line_before_statement' => [
            'statements' => [
                'break',
                'case',
                'continue',
                'declare',
                'default',
                'do',
                'exit',
                'for',
                'foreach',
                'goto',
                'if',
                'include',
                'include_once',
                'require',
                'require_once',
                'return',
                'switch',
                'throw',
                'try',
                'while',
            ],
        ],
        'blank_line_between_import_groups' => false,
        'comment_to_phpdoc' => ['ignored_tags' => ['codeCoverageIgnoreStart', 'codeCoverageIgnoreEnd']],
        'concat_space' => ['spacing' => 'one'],
        'doctrine_annotation_array_assignment' => ['operator' => '='],
        'general_phpdoc_annotation_remove' => ['annotations' => ['author', 'since', 'package', 'subpackage']],
        'header_comment' => ['header' => $header],
        'list_syntax' => ['syntax' => 'short'],
        'mb_str_functions' => true,
        'method_argument_space' => ['on_multiline' => 'ignore'],
        'no_extra_blank_lines' => ['tokens' => ['extra', 'use_trait']],
        'no_superfluous_phpdoc_tags' => ['remove_inheritdoc' => true],
        'not_operator_with_successor_space' => true,
        'ordered_class_elements' => false,
        'ordered_imports' => [
            'imports_order' => ['const', 'class', 'function'],
        ],
        'php_unit_method_casing' => ['case' => 'snake_case'],
        'php_unit_strict' => false,
        'php_unit_test_annotation' => ['style' => 'annotation'],
        'php_unit_test_class_requires_covers' => false,
        'phpdoc_to_comment' => false,
        'phpdoc_var_without_name' => false,
        'self_static_accessor' => true,
        'single_line_throw' => false,
        'static_lambda' => true,
        'strict_comparison' => false,
        'types_spaces' => ['space' => 'single'],
        'yoda_style' => ['equal' => false, 'identical' => false],
    ])
    ->setFinder($finder);

return $config;
