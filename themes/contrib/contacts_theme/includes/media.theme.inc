<?php

/**
 * @file
 * Media related theme functions and hooks for Contacts Theme.
 */

/**
 * Implements hook_preprocess_HOOK() for image_style.
 */
function contacts_theme_preprocess_image_style(&$variables) {
  if (substr($variables['style_name'], 0, 9) == 'contacts_') {
    $variables['image']['#attributes']['class'][] = 'img-thumbnail';

    // Remove the height/width as they conflict with the image padding/border.
    unset($variables['image']['#width'], $variables['image']['#height']);
  }
}
