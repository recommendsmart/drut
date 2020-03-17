<?php

/**
 * @file
 * Theme and preprocess functions for system
 */

function admin_lte_preprocess_html(&$variables) {
  $skin = theme_get_setting('skin');
  $variables['attributes']['class'][] = empty($skin) ? 'skin-blue' : $skin;
  $variables['attributes']['class'][] = 'sidebar-mini';
}

function admin_lte_preprocess_page(&$variables) {
  $variables['site_name'] = \Drupal::config('system.site')->get('name');
  $variables['site_mini_name'] = \Drupal::config('system.site')->get('mini_name');
}
