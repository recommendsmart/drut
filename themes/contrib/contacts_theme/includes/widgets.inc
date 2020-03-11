<?php

/**
 * @file
 * Alterations for widgets for Contacts Theme.
 */

/**
 * Implements hook_field_widget_WIDGET_TYPE_form_alter().
 */
function contacts_theme_field_widget_datetime_datelist_form_alter(&$element, \Drupal\Core\Form\FormStateInterface $form_state, $context) {
  $element['#theme_wrappers'] = ['form_element'];
}

/**
 * Implements hook_field_widget_WIDGET_TYPE_form_alter().
 */
function contacts_theme_field_widget_address_default_form_alter(&$element, \Drupal\Core\Form\FormStateInterface $form_state, $context) {
  // Only use the wrapper if explicitly asked.
  if (empty($element['#use_details'])) {
    unset($element['#type']);
  }
}
