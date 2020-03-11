<?php

/**
 * @file
 * Field related theme functions and hooks for Contacts Theme.
 */

/**
 * Implement hook_preprocess_field().
 */
function contacts_theme_preprocess_field(&$variables) {
  // If we have columns, everything aside from 'above' should be aligned.
  if ($variables['label_display'] != 'above' && !empty($variables['element']['#columns'])) {
    $variables['attributes']['class'][] = 'row';

    // Add the content classes.
    $variables['content_attributes'] += ['class' => []];
    $variables['content_attributes']['class'] = array_merge($variables['content_attributes']['class'], $variables['element']['#columns']['content']);

    // Add the title classes.
    if ($variables['label_display'] == 'inline') {
      $variables['title_attributes'] += ['class' => []];
      $variables['title_attributes']['class'] = array_merge($variables['title_attributes']['class'], $variables['element']['#columns']['label']);
    }
    // If there is no title, add offsets to the conten.
    else {
      $variables['content_attributes']['class'] = array_merge($variables['content_attributes']['class'], $variables['element']['#columns']['_offset']);
    }
  }
  // If we are inline without columns, do simple inline blocks.
  elseif ($variables['label_display'] == 'inline') {
    $variables['title_attributes']['class'][] = 'd-inline-block';
    $variables['content_attributes']['class'][] = 'd-inline-block';
    $variables['content_attributes']['class'][] = 'align-top';
  }
}
