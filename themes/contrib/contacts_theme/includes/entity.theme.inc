<?php

/**
 * @file
 * Entity related theme functions and hooks for Contacts Theme.
 */

use Drupal\Core\Render\Element;

/**
 * Preprocess callback for entities.
 *
 * @see \contacts_theme_preprocess()
 */
function _contacts_theme_preprocess_entity(&$variables, $type) {
  // Propagate column settings to the fields.
  if (!empty($variables['elements']['#columns'])) {
    // Set some defaults.
    if (!is_array($variables['elements']['#columns'])) {
      $variables['elements']['#columns'] = [
        'label' => ['col-sm-6', 'col-md-4', 'col-lg-3', 'col-xl-2'],
        'content' => ['col-sm'],
        '_offset' => ['offset-sm-6', 'offset-md-4', 'offset-lg-3', 'offset-xl-2'],
      ];
    }

    // Propagate the settings to our children.
    foreach (Element::children($variables['content']) as $child) {
      if (isset($variables['content'][$child]['#theme']) && $variables['content'][$child]['#theme'] == 'field') {
        if (!isset($variables['content'][$child]['#columns'])) {
          $variables['content'][$child]['#columns'] = $variables['elements']['#columns'];
        }
      }
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for user.
 */
function contacts_theme_preprocess_user(&$variables) {
  if ($variables['elements']['#view_mode'] == 'contacts_dashboard_row_small') {
    $variables['title_tag'] = 'h6';
  }
  elseif ($variables['elements']['#view_mode'] == 'contacts_dashboard_row') {
    $variables['title_tag'] = 'h4';
  }
}
