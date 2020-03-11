<?php

/**
 * @file
 * Form alters for Contacts Theme.
 */

use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_alter().
 */
function contacts_theme_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // Check if this is a confirm form and we are putting this in a modal.
  if ($form_state->getFormObject() instanceof ConfirmFormInterface) {
    if (\Drupal::request()->query->get('_wrapper_format') == 'drupal_modal') {
      $form['_title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h5',
        '#value' => $form['#title'],
        '#weight' => -99,
      ];
      $form['#title'] = '';
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function contacts_theme_form_views_exposed_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if ($form_state->get('view')->id() == 'contacts_dashboard_indexed') {
    $form['#attributes']['class'][] = 'contacts-search-form';

    // Hide the label for the search and use a placeholder.
    $form['search']['#title_display'] = 'invisible';
    $form['search']['#attributes']['placeholder'] = $form['#info']['filter-search_api_fulltext']['label'];

    // Make the reset look like a link.
    $form['actions']['reset']['#attributes']['class'][] = 'btn';
    $form['actions']['reset']['#attributes']['class'][] = 'btn-link';
    $form['actions']['reset']['#attributes']['class'][] = 'p-0';

    // Turn the submit into an add-on for the input.
    $form['search']['#has_addons'] = TRUE;
    $form['search']['submit'] = $form['actions']['submit'];
    $form['search']['submit']['#weight'] = 1;
    $form['search']['submit']['#attributes']['class'][] = 'btn';
    $form['search']['submit']['#attributes']['class'][] = 'btn-primary';
    unset($form['actions']['submit']);
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 *  Apply columns to the profile form.
 */
function contacts_theme_form_profile_form_alter(&$form, FormStateInterface $form_state) {
  if ($form_state->getFormObject()->getOperation() == 'crm_dashboard') {
    $form['#form_columns'] = TRUE;
    $form['#pre_render'][] = 'contacts_theme_form_propagate_columns';
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 *  Apply columns to the user form.
 */
function contacts_theme_form_user_form_alter(&$form, FormStateInterface $form_state) {
  // @todo: Add a CRM form mode for users.
  if ($form_state->getFormObject()->getOperation() == 'default') {
    $form['#form_columns'] = TRUE;
    $form['#pre_render'][] = 'contacts_theme_form_propagate_columns';
  }
}
