<?php

/**
 * @file
 * Customize theme settings from the Bulma base theme.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for system_theme_settings.
 */
function octavia_form_system_theme_settings_alter(&$form, FormStateInterface $form_state, $form_id = NULL) {
  // We compile and use our own customized Bulma CSS, so hide the CDN details.
  $form['cdn']['#access'] = FALSE;
}
