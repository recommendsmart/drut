<?php

/**
 * @file
 * Utility functions for Contacts Theme.
 */

use Drupal\Core\Access\AccessResultInterface;

/**
 * Get/set the header image.
 *
 * @param array|NULL $content
 *   The render array for the header image if setting.
 *
 * @return array|NULL
 *   The render array for the header image or NULL if there is none.
 */
function contacts_theme_header_image(array $content = NULL) {
  $image = &drupal_static('contacts_theme_header_image');
  if ($content) {
    $image = $content;
  }
  return $image;
}

/**
 * Check the access on an element.
 *
 * @param array $element
 *   A render element. #access will be filled out.
 *
 * @return bool
 *   The access result.
 *
 * @see \Drupal\Core\Render\Renderer::doRender()
 */
function contacts_theme_check_access(array &$element) {
  // Process access callbacks.
  if (!isset($element['#access']) && isset($element['access_callback'])) {
    if (is_string($element['#access_callback']) && strpos($element['#access_callback'], '::') === FALSE) {
      $element['#access_callback'] = \Drupal::service('controller_resolver')->getControllerFromDefinition($element['#access_callback']);
    }
    $element['#access'] = call_user_func($element['#access_callback'], $element);
  }

  // If there is no access, it's accessible.
  if (!isset($element['#access'])) {
    $element['#access'] = TRUE;
  }

  // If #access is an AccessResultInterface object, we need a method call.
  if ($element['#access'] instanceof AccessResultInterface) {
    return $element['#access']->isAllowed();
  }

  return (bool) $element['#access'];
}
