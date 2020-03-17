<?php

/**
 * @file
 * Custom theme hooks.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function admin_lte_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
  $form['skin'] = [
    '#type' => 'select',
    '#title' => t('Skin'),
    '#options' => [
      'skin-blue' => t('Blue'),
      'skin-black' => t('Black'),
      'skin-purple' => t('Purple'),
      'skin-green' => t('Green'),
      'skin-red' => t('Red'),
      'skin-yellow' => t('Yellow'),
      'skin-blue-light' => t('Blue Light'),
      'skin-black-light' => t('Black Light'),
      'skin-purple-light' => t('Purple Light'),
      'skin-green-light' => t('Green Light'),
      'skin-red-light' => t('Red Light'),
      'skin-yellow-light' => t('Yellow Light'),
    ],
    '#default_value' => theme_get_setting('skin'),
  ];
}
