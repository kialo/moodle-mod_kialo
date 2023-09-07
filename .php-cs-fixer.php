<?php

$finder = PhpCsFixer\Finder::create()
    ->ignoreDotFiles(false)
    ->ignoreUnreadableDirs()
    ->in(__DIR__)
    ->exclude('development');

$config = new PhpCsFixer\Config();

return $config->setRules([
    // Moodle's coding style is based on PSR12 and PSR1.
    '@PSR12' => true,
    '@PSR1' => true,
    // But has a lot of custom rules (https://moodledev.io/general/development/policies/codingstyle).
    'curly_braces_position' => [
        'control_structures_opening_brace' => 'same_line',
        'functions_opening_brace' => 'same_line',
        'anonymous_functions_opening_brace' => 'same_line',
        'classes_opening_brace' => 'same_line',
        'anonymous_classes_opening_brace' => 'same_line',
    ],
    'blank_line_after_opening_tag' => false,
    'no_blank_lines_after_class_opening' => false,
    'elseif' => false,
])->setFinder($finder);
