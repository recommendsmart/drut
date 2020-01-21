<?php

/**
 * @file
 * Integrates the OpenBusiness theme with the color module.
 */

$info = [
  // Available colors and color labels used in theme.
  'fields' => [
    'primary' => t('Primary Color'),
    'secondary' => t('Secondary Color'),
    'text' => t('Text Color'),
    'background' => t('Background Color'),
    'hover' => t('Hover Color'),
  ],
  // Pre-defined color schemes.
  'schemes' => [
    'default' => [
      'title' => 'Lite',
      'colors' => [
        'primary' => '#c5198d',
        'secondary' => '#e8edf0',
        'text' => '#454545',
        'background' => '#ffffff',
        'hover' => '#af0c7a',
      ],
    ],
    'Dark' => [
      'title' => 'Dark',
      'colors' => [
        'primary' => '#c5198d',
        'secondary' => '#1C3877',
        'text' => '#ffffff',
        'background' => '#132347',
        'hover' => '#af0c7a',
      ],
    ],
  ],
];

// CSS files (excluding @import) to rewrite with new color scheme.
$info['css'] = ['css/color.css'];
// Files to copy.
$info['copy'] = ['logo.svg'];
